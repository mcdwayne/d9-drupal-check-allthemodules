<?php

namespace Drupal\private_taxonomy\Type;

use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'private_taxonomy_term_reference' entity field item.
 */
class PrivateTaxonomyTermReferenceItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @var array
   *
   * @see PrivateTaxonomyTermReferenceItem::getPropertyDefinitions()
   */
  protected $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['tid'] = [
        'type' => 'integer',
        'label' => $this->t('Referenced taxonomy term id.'),
      ];
      static::$propertyDefinitions['entity'] = [
        'type' => 'entity',
        'constraints' => [
          'EntityType' => 'taxonomy_term',
        ],
        'label' => $this->t('Term'),
        'description' => $this->t('The referenced taxonomy term'),
        // The entity object is computed out of the tid.
        'computed' => TRUE,
        'read-only' => FALSE,
        'settings' => ['id source' => 'tid'],
      ];
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::setValue().
   */
  public function setValue($values) {
    // Treat the values as property value of the entity field, if no array
    // is given.
    if (!is_array($values)) {
      $values = ['entity' => $values];
    }

    // Entity is computed out of the ID, so we only need to update the ID. Only
    // set the entity field if no ID is given.
    if (isset($values['tid'])) {
      $this->properties['tid']->setValue($values['tid']);
    }
    elseif (isset($values['entity'])) {
      $this->properties['entity']->setValue($values['entity']);
    }
    else {
      $this->properties['entity']->setValue(NULL);
    }
  }

}
