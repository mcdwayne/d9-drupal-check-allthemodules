<?php

namespace Drupal\minimum_cardinality_required\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay as BaseEntityFormDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extend the EntityFormDisplay.
 */
class EntityFormDisplay extends BaseEntityFormDisplay {

  /**
   * {@inheritdoc}
   */
  public function buildForm(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    // Let each widget generate the form elements.
    foreach ($this->getComponents() as $name => $options) {
      if ($widget = $this->getRenderer($name)) {
        $items = $entity->get($name);
        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');

        // Assign the correct weight. This duplicates the reordering done in
        // processForm(), but is needed for other forms calling this method
        // directly.
        $form[$name]['#weight'] = $options['weight'];

        // Associate the cache tags for the field definition & field storage
        // definition.
        $field_definition = $this->getFieldDefinition($name);
        $this->renderer->addCacheableDependency($form[$name], $field_definition);
        $this->renderer->addCacheableDependency($form[$name], $field_definition->getFieldStorageDefinition());
      }
    }

    // Associate the cache tags for the form display.
    $this->renderer->addCacheableDependency($form, $this);
    // Add a process callback so we can assign weights and hide extra fields.
    $form['#process'][] = [$this, 'processForm'];

  }

}
