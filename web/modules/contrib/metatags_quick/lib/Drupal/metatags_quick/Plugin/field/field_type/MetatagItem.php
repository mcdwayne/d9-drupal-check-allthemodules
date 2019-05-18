<?php
/**
 * @file
 * Contains \Drupal\metatags_quick\Plugin\field\field_type\MetatagItem.
 */

namespace Drupal\metatags_quick\Plugin\field\field_type;

use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'metatags_quick' field type.
 *
 * @FieldType(
 *   id = "metatags_quick",
 *   label = @Translation("Meta tag"),
 *   description = @Translation("This field stores meta tags."),
 *   instance_settings = {
 *     "min" = "",
 *     "max" = "",
 *     "prefix" = "",
 *     "suffix" = "",
 *     "meta_name" = "",
 *   },
 *   default_widget = "metatags_quick_default",
 *   default_formatter = "metatag_formatter_default"
 * )
 */
class MetatagItem extends ConfigFieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 1024,
          'not null' => FALSE,
        ),
      ),
    );
  }

}
