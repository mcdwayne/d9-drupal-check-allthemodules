<?php

namespace Drupal\entity_pilot\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting a departure entity.
 */
class DepartureDeleteForm extends FlightDeleteFormBase {

  /**
   * Redirect route name.
   *
   * @var string
   */
  protected $redirectRouteName = 'entity_pilot.departure_list';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#markup' => $this->t('Note that this will not remove flights from remote storage or sites.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
