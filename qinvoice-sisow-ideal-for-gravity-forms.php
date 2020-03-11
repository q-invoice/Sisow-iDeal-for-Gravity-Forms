<?php

/*
Plugin Name: q-invoice Sisow iDeal for Gravity Forms
Plugin URI: https://github.com/q-invoice/Sisow-iDeal-for-Gravity-Forms
Description: Accept iDeal (and other) payments through Sisow for your Gravity Forms
Version: 0.0.1
Author: q-invoice
Author URI: http://www.q-invoice.com
Text Domain: qinvoice-sisow-ideal-for-gravity-forms
Domain Path: /languages

*/

function qinvoice_sisow_for_gravity_forms_load_textdomain()
{
    load_plugin_textdomain('qinvoice-sisow-ideal-for-gravity-forms', false, basename(dirname(__FILE__)) . '/languages');
}

add_action('plugins_loaded', 'qinvoice_sisow_for_gravity_forms_load_textdomain');

add_action('gform_loaded', array('GF_Qinvoice_Sisow_Bootstrap', 'load'), 5);

class GF_Qinvoice_Sisow_Bootstrap
{

    public static function load()
    {

        if (!method_exists('GFForms',  'include_addon_framework')) {
            return;
        }

        require_once('sisow/sisowapi.php');
        require_once('class-qinvoice-sisow.php');

        GFAddOn::register('QinvoiceSisow');

        add_action( 'wp', array( 'QinvoiceSisow', 'handle_confirmation' ), 4 );

        add_action( 'admin_notices', array( 'QinvoiceSisow', 'maybe_show_admin_notice_test_mode' ));


    }

}
