# Commerce USAePay

Provides Drupal Commerce integration for USAePay gateway.

## Prerequisites

This module has the following prerequisites:

- The PHP SOAP extension must be installed. See
http://php.net/manual/en/soap.installation.php.

- A USAePay API WSDL endpoint can be created as follows:
	- Login to https://sandbox.usaepay.com/_developer/app/login.
	- From the menu at the left, click on 'API Endpoints'.
	- Add a new SOAP endpoint. This module was developed with a version
	  1.6 RPC/Encoded WSDL endpoint. Doc/Literal did not work.
	- Record the generated key. Ex: ABCD1234.

- The merchant will also need to create a source key and PIN number via the
USAePay Merchant Console.

## Installation

This module is installed just like any other Drupal module. No external Composer
libraries are required.

## Configuration

Simply enter a payment gateway name, the WSDL key, source key and PIN obtained
as outlined in the Prerequisites section to the 'Add payment gateway' form.

USAePay supports 3 modes: live, test and sandbox. This module does not support
the test mode so choosing 'Test' on the configuration form will activate USAePay
'Sandbox' mode.
