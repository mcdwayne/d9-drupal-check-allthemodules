# Drupal Google+ Login

Allows users to log into your Drupal site with their Google account. An option
is available to override the `user/login` path to direct all login requests to
Google (Note: if this option is enabled the Drupal login form is available at 
`user/site-login`).

## Installation Instructions

The recommended installation option is through Composer:

```bash
$ composer require drupal/google_plus_login
```

## Setup Instructions

* Enable the `google_plus_login` module
* [Follow the Google Developer documentation for enabling Google Signin for Google+]
* Enter the Client ID and Client Secret into the module's configuration
(Configuration -> Web Services -> Google OAuth Login)

[Follow the Google Developer documentation for enabling Google Signin for Google+]: https://developers.google.com/+/web/signin/
