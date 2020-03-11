=== q-invoice Sisow iDeal for Gravity Forms ===
Contributors: q-invoice
Donate link: n/a
Tags: ideal, sisow, paypal, payment, pay, invoice
Requires at least: 4.0
Tested up to: 5.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Sisow iDeal and other payment methods to your Gravity Forms.

== Description ==

This plugin allows you to integrate and use Sisow payments for your Gravity Forms. No subscription is required.


== Installation ==

Install the plugin by uploading the zipfile in your WP admin interface or via FTP:

1. Upload the folder `sisow-ideal-for-gravity-forms` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find the configuration page and fill out your settings and preferences

== Frequently Asked Questions ==

= Is this plugin absolutely free to use? =

Yes. The plugin is free. All you need is a [Sisow account](https://www.sisow.nl/aanmelden/?r=309206).

= What if I want to create and send beautiful invoices as well? =

Check out our invoicing plugin for Gravity Forms:
- [Wordpress repository](https://wordpress.org/plugins/qinvoice-connect-for-gravity-forms/)
- [Github](https://github.com/q-invoice/qinvoice-connect-for-gravity-forms)

= Can I use Mistercash, DIRECTebanking and/or Credit card payments? =

Yes you can. Because of the design of Sisow's API you will need to create a different feed for each payment method though.
Secondly, add a dropdown (or any other field) to your form and let the user pick their preferred payment method before submitting.
Using conditional logic in the feed setting you can now present the correct payment method based on the user's input.
- The availability of payment methods depends on your profile and your contract with Sisow.

= Do you offer support? =

Sure we do. Contact us at support@q-invoice.com.

== Changelog ==

= 0.0.1 =
* Initial commit; first public version

== Upgrade Notice ==

No upgrade notices apply.

== Arbitrary section ==

= Testmode =
To enable testmode you should allow test transaction for your profile. To do so, login to Sisow and find the setting under 'profile'.
Furthermore, you need to enable testmode for the plugin. Go to Forms -> Settings and find the checkbox.
- Please note that once enabled, the testmode applies to ALL FEEDS.

