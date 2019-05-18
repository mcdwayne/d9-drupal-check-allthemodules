ABOUT THIS MODULE
-----------------

Implements Google Tag Manager (GTM) and IP Country Code lookup (Using IPAPI
or GEOIP).
Sets GTM dataLayer variable with continent code value which allows you to
trigger or disable tracking scripts to help make the site GDPR compliant.

Option to add a cookie and duration so IP lookup only happens 1 time. (Module
only sets the cookie for US ip addresses currently.)

Show a cookie consent popup using the cookie consent library. With the option
to disable pop-up for North American IP addresses.
https://cookieconsent.insites.com

HOW TO INSTALL
--------------

Place the gdpr_tag_manager directory in your modules directory, enable the module
at admin/modules and go to admin/config/gdpr.
