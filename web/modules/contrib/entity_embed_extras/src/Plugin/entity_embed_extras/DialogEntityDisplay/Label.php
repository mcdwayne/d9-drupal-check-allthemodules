<?php

namespace Drupal\entity_embed_extras\Plugin\entity_embed_extras\DialogEntityDisplay;

use Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default label display.
 *
 * @DialogEntityDisplay(
 *   id = "label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the entity's label in the review step.")
 * )
 */
class Label extends DialogEntityDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElement(EntityInterface $entity, array &$original_form, FormStateInterface $form_state) {
    return [
      '#type' => 'item',
      '#title' => $this->t('Selected entity'),
      '#markup' => $entity->label(),
    ];
  }

}
