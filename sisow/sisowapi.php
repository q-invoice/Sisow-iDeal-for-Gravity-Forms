<?php

class SisowApi
{

    protected static $issuers;
    protected static $lastcheck;

    private $response;

// Merchant data
    private $merchantId;
    private $merchantKey;

// Transaction data
    public $payment;    // empty=iDEAL; sofort=DIRECTebanking; mistercash=MisterCash; ...
    public $issuerId;    // mandatory; sisow bank code
    public $purchaseId;    // mandatory; max 16 alphanumeric
    public $entranceCode;    // max 40 strict alphanumeric (letters and numbers only)
    public $description;    // mandatory; max 32 alphanumeric
    public $amount;        // mandatory; min 0.45
    public $notifyUrl;
    public $returnUrl;    // mandatory
    public $cancelUrl;
    public $callbackUrl;
    private $testmode = "";

// Status data
    public $status;
    public $timeStamp;
    public $consumerAccount;
    public $consumerName;
    public $consumerCity;

// Result/check data
    public $trxId;
    public $issuerUrl;

// Error data
    public $hasErrors = false;
    public $errorCode;
    public $errorMessage;

// Status
    const statusSuccess = "Success";
    const statusCancelled = "Cancelled";
    const statusExpired = "Expired";
    const statusFailure = "Failure";
    const statusOpen = "Open";

    public function __construct($merchantid, $merchantkey)
    {

        $this->merchantId = $merchantid;
        $this->merchantKey = $merchantkey;
    }

    private function error()
    {
        $this->hasErrors = true;
        $this->errorCode = $this->parse("errorcode");
        $this->errorMessage = urldecode($this->parse("errormessage"));
    }

    private function parse($search, $xml = false)
    {
        if ($xml === false) {
            $xml = $this->response;
        }
        if (($start = strpos($xml, "<" . $search . ">")) === false) {
            return false;
        }
        $start += strlen($search) + 2;
        if (($end = strpos($xml, "</" . $search . ">", $start)) === false) {
            return false;
        }
        return substr($xml, $start, $end - $start);
    }

    private function send($method, array $body = NULL, $return = 1)
    {

        $args = array(
            'body' => $body,
            'timeout' => '60',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'cookies' => array()
        );

        $response = wp_remote_post( "https://www.sisow.nl/Sisow/iDeal/RestHandler.ashx/" . $method, $args );
        $this->response = $response['body'];

        if (!$this->response) {
            return false;
        }
        return true;
    }

    private function getDirectory()
    {
        $diff = 24 * 60 * 60;
        if (self::$lastcheck)
            $diff = time() - self::$lastcheck;
        if ($diff < 24 * 60 * 60)
            return 0;
        if (!$this->send("DirectoryRequest"))
            return -1;
        $search = $this->parse("directory");
        if (!$search) {
            $this->error();
            return -2;
        }
        self::$issuers = array();
        $iss = explode("<issuer>", str_replace("</issuer>", "", $search));
        foreach ($iss as $k => $v) {
            $issuerid = $this->parse("issuerid", $v);
            $issuername = $this->parse("issuername", $v);
            if ($issuerid && $issuername) {
                self::$issuers[$issuerid] = $issuername;
            }
        }
        self::$lastcheck = time();
        return 0;
    }

    public function setTestmode(){
        $this->testmode = "true";
    }
// DirectoryRequest
    public function DirectoryRequest(&$output, $select = false, $test = false)
    {

        if ($test === true) {
            $output = array("99" => "Sisow Bank (test)");
            return $output;
        }
        $output = false;
        $ex = $this->getDirectory();
        if ($ex < 0) {
            return $ex;
        }
        if ($select === true) {
            $output = "<select id=\"sisowbank\" name=\"issuerid\">";
        } else {
            $output = array();
        }
        foreach (self::$issuers as $k => $v) {
            if ($select === true) {
                $output .= "<option value=\"" . $k . "\">" . $v . "</option>";
            } else {
                $output[$k] = $v;
            }
        }
        if ($select === true) {
            $output .= "</select>";
        }
        return $output;
    }

// TransactionRequest
    public function TransactionRequest($keyvalue = NULL)
    {
        $this->trxId = $this->issuerUrl = "";
        if (!$this->merchantId)
            throw new \Exception("Merchant ID incorrect or missing");
        if (!$this->merchantKey)
            throw new \Exception("Merchant key incorrect or missing");
        if (!$this->purchaseId)
            throw new \Exception("Purhcase ID incorrect or missing");

        if ($this->amount < 0.45)
            throw new \Exception("Amount too low");
        if (!$this->description)
            throw new \Exception("Description incorrect or missing");

        if (!$this->returnUrl)
            throw new \Exception("Return URL incorrect or missing");

        if (!$this->issuerId && !$this->payment)
            throw new \Exception("Method/Issuer incorrect or missing");

        if (!$this->entranceCode)
            $this->entranceCode = $this->purchaseId;
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
        $pars["payment"] = $this->payment;
        $pars["issuerid"] = $this->issuerId;
        $pars["purchaseid"] = $this->purchaseId;
        $pars["amount"] = round($this->amount);
        $pars["description"] = $this->description;
        $pars["entrancecode"] = $this->entranceCode;
        $pars["returnurl"] = $this->returnUrl;
        $pars["cancelurl"] = $this->cancelUrl;
        $pars["callbackurl"] = $this->callbackUrl;
        $pars["notifyurl"] = $this->notifyUrl;
        $pars["testmode"] = "true";
        $pars["sha1"] = sha1($this->purchaseId . $this->entranceCode . round($this->amount) . $this->merchantId . $this->merchantKey);
        if ($keyvalue) {
            foreach ($keyvalue as $k => $v) {
                if ($k != 'amount') {
                    $pars[$k] = $v;
                }
            }
        }


        if (!$this->send("TransactionRequest", $pars)) {
            throw new \Exception("Could not send request");
        }

        $this->trxId = $this->parse("trxid");
        $this->issuerUrl = urldecode($this->parse("issuerurl"));
        if (!$this->issuerUrl) {
            throw new \Exception(sprintf("Unknown error (%s)", $this->response));

        }
        return 0;
    }

// StatusRequest
    public function StatusRequest($trxid = false)
    {
        if ($trxid === false)
            $trxid = $this->trxId;
        if (!$this->merchantId)
            throw new \Exception("Merchant ID is missing or incorrect");
        if (!$this->merchantKey)
            throw new \Exception("Merchant key is missing or incorrect");
        if (!$trxid)
            throw new \Exception("Transaction ID is missing or incorrect");
        $this->trxId = $trxid;
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
        $pars["trxid"] = $this->trxId;
        $pars["sha1"] = sha1($this->trxId . $this->merchantId . $this->merchantKey);
        if (!$this->send("StatusRequest", $pars))
            throw new \Exception("Could not send request");
        $this->status = $this->parse("status");
        if (!$this->status) {
            throw new \Exception("Could not retrieve status");
        }
        $this->timeStamp = $this->parse("timestamp");
        $this->amount = $this->parse("amount") / 100.0;
        $this->consumerAccount = $this->parse("consumeraccount");
        $this->consumerName = $this->parse("consumername");
        $this->consumerCity = $this->parse("consumercity");
        $this->purchaseId = $this->parse("purchaseid");
        $this->description = $this->parse("description");
        $this->entranceCode = $this->parse("entrancecode");
        return 0;
    }
}
