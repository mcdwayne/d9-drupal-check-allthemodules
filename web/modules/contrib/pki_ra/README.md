# PKI Registration Authority

## Purpose

This module allows your site to act as registration authority (RA) as part of a [public key infrastructure (PKI)](https://en.wikipedia.org/wiki/Public_key_infrastructure).

Once registered, users will be able to generate certificates they can use as credentials. For example, such certificates can be used to log into sites running [Certificate Login](https://www.drupal.org/project/certificatelogin) without usernames or passwords.

## How it works

### Registration process

In order for a user to obtain a certificate from the system, he/she must go through the registration process:

1. Surf to `/registration/start`, where relevant information will be displayed.
1. Click on the link to begin registration.
1. Enter an e-mail address, and submit the form.
1. A verification link (security token) will be e-mailed to the provided address.
1. By visiting this URL, the user can validate the e-mail address.
1. He or she is taken to a certificate generation form.
1. The user enters a passphrase (and optionally chooses a key type, elliptic curve or RSA).
1. On submission of the form, a [PKCS #12](https://en.wikipedia.org/wiki/PKCS_12) file is generated locally.  It contains the user's private key and certificate (along with some other items).
1. After importing the file into his/her browser, he/she can log into sites using the generated certificate.

### Certificate generation

There's some behind-the-scenes work necessary to generate the certificate.

1. After hitting the *Generate certificate* button, the JavaScript running on the client takes over.
1. A [key pair](https://en.wikipedia.org/wiki/Public-key_cryptography) is generated, with the private key encrypted with the user-provided passphrase.
1. A [certificate signing request (CSR)](https://en.wikipedia.org/wiki/Certificate_signing_request) is generated using the key pair.
1. A Web service call over [REST](https://en.wikipedia.org/wiki/Representational_state_transfer) is sent to the Drupal server by POSTing the CSR.
1. The server forwards the request to the configured [certification authority (CA)](https://en.wikipedia.org/wiki/Certificate_authority).
1. Drupal receives the certificate bundle from the CA, stores relevant certificate information (e.g. the expiry date), and then returns the bundle to the client.

The above process guarantees that the user's private key never leaves the client.

### Certificate use

[Certificate Login](https://www.drupal.org/project/certificatelogin) will be made available for Drupal 8 to work in combination with this module to allow users to create accounts and log in with the certificates provided here.

These other modules provide the same functionality as Certificate Login, but at the time of this writing there are no Drupal 8 plans, and there is no coordination with them.

* [PKI Authentication](https://www.drupal.org/sandbox/rickwelch/1663258)
* [Certificate Auto Login](https://www.drupal.org/sandbox/yvmarques/1276604)

## Dependencies

* The [enrollment-js](https://gitlab.com/authenticity/enrollment-js/) JavaScript library.
* A CA, which can accept CSRs and return certificates.  A default one will be available shortly.
* Drupal must be able to send mail in order to validate e-mail addresses.  See [How to have my PHP Send mail?](https://askubuntu.com/questions/47609/how-to-have-my-php-send-mail/128009) for details.
* Some type of [CAPTCHA](https://en.wikipedia.org/wiki/CAPTCHA) (e.g. [Honeypot](https://www.drupal.org/project/honeypot)) on the registration form to prevent confirmation e-mails from being sent to spam bots.
* Mandatory HTTPS as Web browsers are now blocking insecure Web service calls.  (See note below for handling development.)  This fairly easy to set up nowadays given that [Let's Encrypt](https://letsencrypt.org/) is up and running, or trivial with the [Caddy](https://caddyserver.com/) Web server.

## Set-up instructions

1. Download the JS library from [GitLab](https://gitlab.com/authenticity/enrollment-js/tags).
1. Place the .js file in the root libraries folder, `/libraries`.
1. Rename it and add to `/libraries` folder like this `/libraries/pki_ra/enrollment.min.js`.
1. Install the module.
1. Surf to *Administration » Configuration » Web services » PKI Registration Authority* to configure the basic settings. For environment-specific settings, it's better to keep these in the site's local settings file (usually `settings.local.php` or `local.settings.php` on [Aegir](http://docs.aegirproject.org/)) instead of the default site configuration, the database.
    * `// Environment-specific settings for PKI Registration Authority.`
    * `$config['pki_ra.settings']['certificate_authority_url'] = 'https://staging.ca.example.com';`
    * `$config['pki_ra.settings']['certificate_authority_authentication_header'] = 'Authorization: dSe0RkZpu3aBiy62h7sEqyz2amNUt8DRx5V6E3092g';`
1. Optionally click on the other tabs to configure optional components.

## Features

### Views

Two views are provided, one listing Registrations and another listing Certificates.  Their links are available under PKI RA in the Administration menu.

### Configurable user-facing messages

All of the major messaging provided to end users is configurable in the module's configuration.

## Notes

### Running on an insecure site

If running on an insecure site (like your local development laptop) over HTTP instead of HTTPS with Chrome/Chromium, nothing will happen when you click the *Generate certificate* button. Open the developer tools, try again, and you should see this in the console:

> Only secure origins are allowed

What's happening is that the AJAX call is being blocked by the browser because it's not an HTTPS URL, which you would definitely be running in Production.

To work around this, run the browser like so, with desired values for the parameters:

`chromium-browser --unsafely-treat-insecure-origin-as-secure=http://local.example.com --user-data-dir=/tmp/foo &`

See [Why am I seeing “Error - Only secure origins are allowed” for my service worker?](https://stackoverflow.com/questions/41373166/why-am-i-seeing-error-only-secure-origins-are-allowed-for-my-service-worker) for more information.

### EOI - Evidence of Identity

Each source of evidence of identity, or evidence of identity (EOI) source, adds assurance that each user is actually who he/she claims to be. This terminology is used throughout the code.

## Similar Modules

[Clientcert (SSL/TLS) for Login](https://www.drupal.org/project/clientcert): Requires [Rules](https://www.drupal.org/project/rules) for more complicated scenarios, and is only available for Drupal 7.
