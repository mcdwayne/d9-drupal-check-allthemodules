<?php

namespace Drupal\scss_field;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * A simple service tracking which bundles implement SCSS fields.
 */
class ScssFieldManager {

  /**
   * The SCSS field map.
   *
   * @var array
   */
  protected $map;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFieldManager $entity_field_manager) {
    foreach ($entity_field_manager->getFieldMapByFieldType('scss') as $entity_type_id => $fields) {
      foreach ($fields as $field_name => $field_info) {
        foreach ($field_info['bundles'] as $bundle) {
          $this->map[$entity_type_id][$bundle][] = $field_name;
        }
      }
    }
  }

  /**
   * Return an array of SCSS fields of the given entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   the SCSS fields of the given entity; may be empty
   */
  public function getScssFields(FieldableEntityInterface $entity) {
    $scss_fields = [];
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    if (isset($this->map[$entity_type]) && isset($this->map[$entity_type][$bundle])) {
      foreach ($this->map[$entity_type][$bundle] as $field_name) {
        $scss_fields[$field_name] = $entity->get($field_name);
      }
    }
    return $scss_fields;
  }

}
