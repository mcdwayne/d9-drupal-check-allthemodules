### SUMMARY
This module adds a Stripe Webhook endpoint to to receive notifications of the desired events and launch Symfony events.

### REQUIREMENTS
- [PHP library for the Stripe API](https://github.com/stripe/stripe-php).

### INSTALLATION
Using composer:
```
composer require drupal/stripe_webhooks --sort-packages
```

This module adds support to [Ludwig](https://www.drupal.org/project/ludwig), as alternative to composer.

### CONFIGURATION
- Login your [Stripe account](https://dashboard.stripe.com).
- Go to [API section](https://dashboard.stripe.com/account/apikeys).
![Secret key - Stripe API](https://monosnap.com/file/UtbXCJuAgJhp2brx2h7PrX0cCIBBmh.png)
- Add your `Secret key` to your Drupal `settings.php` file
```
$settings['stripe_webhooks_api_key'] = 'sk_XXXX_XXXXXXXXXXXXXXXXXXXXXXXX';
```
- Add your [Webhook endpoint](https://dashboard.stripe.com/account/webhooks), the path is `http://your-site.com/stripe-webhooks/endpoint`.
![Webhook endpoint - Stripe API](https://monosnap.com/file/S6FJ5nXg2VysIK6lcNU8mmlpdJuAr0.png)
- Add your `Signing secret` key to your Drupal `settings.php` file
![Signing secret key - Stripe API](https://monosnap.com/file/MNfSzlRdBcGbJgjjNSszZIR4UomMlR.png)
```
$settings['stripe_webhooks_signing_secret_key'] = 'whsec_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
```
- Congratulations, now you can receive your Stripe notifications, see our module example for more details.
- This module includes a module example.

### CONTACT
Developed and maintained by Cambrico (http://cambrico.net).

Get in touch with us for customizations and consultancy:
http://cambrico.net/contact

#### Current maintainers:
- Pedro Cambra [(pcambra)](http://drupal.org/user/122101)
- Manuel Egío [(facine)](http://drupal.org/user/1169056)
