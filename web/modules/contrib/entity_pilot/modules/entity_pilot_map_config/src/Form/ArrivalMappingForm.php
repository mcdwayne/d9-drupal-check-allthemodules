<?php

namespace Drupal\entity_pilot_map_config\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot\Form\ArrivalForm;
use Drupal\field_ui\FieldUI;

/**
 * Form controller for the arrival mapping form.
 */
class ArrivalMappingForm extends ArrivalForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $arrival = $this->entity;
    if ($arrival->id()) {
      $request = $this->getRequest();
      if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
        $request->query->remove('destinations');
        $form_state->setRedirectUrl($next_destination);
      }
      else {
        $form_state->setRedirect('entity_pilot.arrival_list');
      }
    }
  }

}
