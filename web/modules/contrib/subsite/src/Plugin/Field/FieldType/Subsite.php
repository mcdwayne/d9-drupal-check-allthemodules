<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 15/01/2016
 * Time: 21:04
 */

namespace Drupal\subsite\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'subsite' entity field type.
 *
 * @FieldType(
 *   id = "subsite",
 *   label = @Translation("Subsite"),
 *   description = @Translation("An entity field for storing subsite settings."),
 *   no_ui = FALSE
 * )
 */
class Subsite extends MapItem {
  public function setValue($values, $notify = TRUE) {
    return parent::setValue($values, $notify);
  }

  public function __get($name) {
    return parent::__get($name);
  }

  public function toArray() {
    return parent::toArray();
  }

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

}