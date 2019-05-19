<?php

/**
 * @file
 * Contains \Drupal\social_comments\Plugin\field\field_type\GoogleItem.
 */

namespace Drupal\social_comments\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'social_comments_google' field type.
 *
 * @FieldType(
 *   id = "social_comments_google",
 *   label = @Translation("Social comments google"),
 *   description = @Translation("Stores a google URL string."),
 *   default_widget = "social_comments",
 *   default_formatter = "social_comments_google"
 * )
 */
class GoogleItem extends SocialItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {}

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {}

  /**
   * {@inheritdoc}
   */
  public function preSave() {}

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {}
}
