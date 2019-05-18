<?php

namespace Drupal\measuremail\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\MeasuremailElementsInterface;
use Drupal\measuremail\MeasuremailInterface;

/**
 * Provides an edit form for measuremail elements.
 *
 * @internal
 */
class MeasuremailElementEditForm extends MeasuremailElementFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MeasuremailInterface $measuremail = NULL, $measuremail_element = NULL) {
    $form = parent::buildForm($form, $form_state, $measuremail, $measuremail_element);

    $form['#title'] = $this->t('Edit %label element', ['%label' => $this->measuremailElement->label()]);
    $form['actions']['submit']['#value'] = $this->t('Update element');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMeasuremailElement($measuremail_element) {
    return $this->measuremail->getElement($measuremail_element);
  }

}
