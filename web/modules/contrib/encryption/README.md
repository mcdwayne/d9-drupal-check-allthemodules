# Encryption #
##############

This module provides a simple two way encryption solution. There are no module
dependencies. It uses openssl which is compiled into php (unless explicitly
omitted) to encrypt/decrypt using AES-256-CTR.


You can use dependency injection or `\Drupal::service('encryption')` to get the
encryption service. The following is a simple example on how the service can be
used:

```php
// Get the encryption service.
$encryption_service = \Drupal::service('encryption');
// Encrypt top secret stuff.
$encrypted_value = $encryption_service->encrypt('big time secrets!');
// Decrypt top secret stuff.
$decrypted_value = $encryption_service->decrypt($encrypted_value);
```
For a configuration form, if you have configuration you don't want exposed in
the file system, you can use the `EncryptionTrait` to add the encrypt/decrypt
methods to your form handler.

```php
<?php

namespace Drupal\example_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encryption\EncryptionTrait;

class ExampleSettings extends ConfigFormBase {

  use Drupal\encryption\EncryptionTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['example_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example Secret'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->decrypt($config->get('example_secret')),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('example_module.settings')
      ->set('example_secret', $this->encrypt($form_state->getValue('example_secret')))
      ->save();
  }
}

```

## Encryption Key ##
####################

The encryption key should go in your settings.php file as a base 64 encoded 256
bit value. A random value can be generated with the following command in Linux.

```bash
dd bs=1 count=32 if=/dev/urandom | openssl base64
```

That value should be added to your settings.php/settings.local.php file:

```php
/**
 * This encryption will be used to encrypt and decrypt values by the encryption
 * module.
 *
 * The value should shared between sites that share encrypted configuration. If
 * the `encryption_key` were to change encrypted data/configuration would be in
 * a corrupt state until the correct encryption key were recovered.
 */
$settings['encryption_key'] = 'IPMj1A1H5w+EMrN5a+w3Y8MUv0CsAAPM5OfaGwMOou4=';
```
