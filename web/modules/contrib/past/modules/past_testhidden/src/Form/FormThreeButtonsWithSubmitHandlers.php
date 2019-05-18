<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormThreeButtonsWithSubmitHandlers extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_three_buttons_with_submit_handlers';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('form handler called by past_testhidden_form_three_buttons_with_submit_handlers');
    $form['sample_property'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#required' => TRUE,
      '#default_value' => 'sample value',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['submit_one'] = [
      '#type' => 'submit',
      '#value' => 'Button 1',
      '#submit' => [[get_class($this), 'customSubmitHandlerButtonOne']],
    ];
    $form['submit_two'] = [
      '#type' => 'submit',
      '#value' => 'Button 2',
      '#submit' => [[get_class($this), 'customSubmitHandlerButtonTwo']],
    ];
    $form['submit_three'] = [
      '#type' => 'submit',
      '#value' => 'Button 3',
      '#submit' => [[get_class($this), 'customSubmitHandlerButtonThree']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(new FormattableMarkup('global submit handler called by @form_id', ['@form_id' => $form['#form_id']]));
  }

  /**
   * Form submit handler for button one.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   */
  public static function customSubmitHandlerButtonOne(array &$form, FormStateInterface $form_state) {
    drupal_set_message(new FormattableMarkup('custom submit handler Button 1 called by @form_id', ['@form_id' => $form['#form_id']]));
  }

  /**
   * Form submit handler for button two.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   */
  public static function customSubmitHandlerButtonTwo(array &$form, FormStateInterface $form_state) {
    drupal_set_message(new FormattableMarkup('custom submit handler Button 2 called by @form_id', ['@form_id' => $form['#form_id']]));
  }

  /**
   * Form submit handler for button three.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   */
  public static function customSubmitHandlerButtonThree(array &$form, FormStateInterface $form_state) {
    drupal_set_message(new FormattableMarkup('custom submit handler Button 3 called by @form_id', ['@form_id' => $form['#form_id']]));
  }

}
