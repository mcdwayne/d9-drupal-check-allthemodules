<?php

/**
 * @file
 * Contains \Drupal\social_comments\Plugin\field\field_type\SocialItemBase.
 */

namespace Drupal\social_comments\Plugin\field\field_type;

use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\FieldInterface;

/**
 * Base class for 'social' configurable field types.
 */
abstract class SocialItemBase extends ConfigFieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['url'] = array(
        'type' => 'uri',
        'label' => t('Social comment URL'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'url' => array(
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $item = $this->getValue();
    // Trim any spaces around the URL and link text.
    $this->url = trim($this->url);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }
}
