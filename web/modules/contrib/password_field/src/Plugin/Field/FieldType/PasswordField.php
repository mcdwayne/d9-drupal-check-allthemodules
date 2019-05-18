<?php

namespace Drupal\password_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of Password.
 *
 * @FieldType(
 *   id = "Password",
 *   label = @Translation("Password field"),
 *   default_widget ="WidgetPassword",
 *   default_formatter = "PasswordFieldFormatter"
 * )
 */
class PasswordField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        // List the values that the field will save. This
        // field will only save a single value, 'value'.
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');

    // $properties['password'] = DataDefinition::create('string')
    //      ->setLabel(t('Password'))
    //      ->setDescription(t('A password saved in plain text.'));.
    return $properties;
  }

  /**
   *
   */
  public function __unset($value) {
    $str = $this->get('value')->getValue();
    $val = $this->encrypt_decrypt(encrypt, $str);
    $this->set('value', $val);
  }

  /**
   *
   */
  public function encrypt_decrypt($action, $string) {
    $output = FALSE;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'This is my secret key';
    $secret_iv = 'This is my secret iv';
    // Hash.
    $key = hash('sha256', $secret_key);

    // Iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning.
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'encrypt') {
      $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
      $output = base64_encode($output);
    }
    else {
      if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
