<?php

namespace Drupal\webform_rut\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'rut_field' element.
 *
 * @WebformElement(
 *   id = "rut_field",
 *   description = @Translation("Provides a form element to enter a rut."),
 *   label = @Translation("Rut"),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Rut extends WebformElementBase {

	/**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'message_js' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#validate_js'] = TRUE;
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['rut_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Rut settings'),
    ];
    $form['rut_field']['message_js'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message by js'),
      '#description' => $this->t('Define the message to display if the javascript validator is checked'),
    ];
    return $form;
  }
}
