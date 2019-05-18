<?php

namespace Drupal\points\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the inline form for Point Entity.
 */
class PointInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    $ief_row_delta = $entity_form['#ief_row_delta'];
    /** @var \Drupal\points\Entity\Point $entity */
    $entity = $entity_form['#entity'];
    $user_inputs = $form_state->getUserInput();
    $points_inputs = NULL;
    if (array_key_exists($entity_form['#parents'][0], $user_inputs)) {
      $field_name = array_slice($entity_form['#parents'], -3, 1)[0];
      if ($field_name == $entity_form['#parents'][0]) {
        $points_inputs = $user_inputs[$entity_form['#parents'][0]];
      }
      else {
        $points_inputs = $this->searchPointsEntity($field_name, $user_inputs[$entity_form['#parents'][0]]);
      }
    }

    // When the point entity was first loaded, we can get it's points value;
    // Set the point entity's state value always be the same with the value
    // above no matter how many times the point entity was loaded.
    if (!isset($points_inputs[$ief_row_delta]['inline_entity_form']['state'])) {
      $entity_form['state'] = [
        '#type' => 'hidden',
        '#value' => $entity->getPoints(),
      ];
      $entity->set('state', $entity->getPoints());
    }
    else {
      $entity->set('state', $points_inputs[$ief_row_delta]['inline_entity_form']['state']);
    }
    return $entity_form;
  }

  /**
   * Search Point entity data in nested IEF.
   *
   * @param string $needle
   *   Point field name.
   * @param array $haystack
   *   Form user input values.
   *
   * @return mixed|null
   *   Point entity data.
   */
  public function searchPointsEntity($needle, array $haystack) {
    $entity_found = NULL;
    foreach ($haystack as $key => $value) {
      if ($key === $needle) {
        $entity_found = $value;
      }
      if (is_array($value)) {
        $this->searchPointsEntity($needle, $value);
      }
    }
    return $entity_found;
  }

}
