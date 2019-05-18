<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting a arrival entity.
 */
class ArrivalDeleteForm extends FlightDeleteFormBase {

  /**
   * Redirect route name.
   *
   * @var string
   */
  protected $redirectRouteName = 'entity_pilot.arrival_list';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#markup' => $this->t('Note that this will not remove entities imported from any previous approval for this arrival.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
