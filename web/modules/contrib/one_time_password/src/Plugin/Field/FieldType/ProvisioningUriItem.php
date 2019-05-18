<?php

namespace Drupal\one_time_password\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\one_time_password\Exception\MissingProvisioningUriException;
use OTPHP\Factory;

/**
 * Plugin implementation of the video_embed_field field type.
 *
 * @FieldType(
 *   id = "one_time_password_provisioning_uri",
 *   label = @Translation("One Time Password Provisioning URI Field"),
 *   description = @Translation("Stores an OTP provisioning URI field."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\one_time_password\Plugin\Field\FieldType\ProvisioningUriItemList"
 * )
 *
 * @internal
 */
class ProvisioningUriItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uri'] = DataDefinition::create('string')
      ->setLabel(t('Password Provisioning URI'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'uri' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * Get the one time password object.
   *
   * @return \OTPHP\TOTP
   *   The one time password object.
   *
   * @throws \Drupal\one_time_password\Exception\MissingProvisioningUriException
   *   If the field URI property is empty, an exception is thrown.
   */
  public function getOneTimePassword() {
    if (empty($this->uri)) {
      throw new MissingProvisioningUriException('Cannot get password, uri property on provisioning field is empty.');
    }
    return Factory::loadFromProvisioningUri($this->uri);
  }

}
