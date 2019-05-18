<?php

namespace Drupal\past_testhidden\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays a form with just an submit button.
 */
class FormMultistep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_testhidden_form_multistep';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = in_array($form_state->get('page_num'), [1, 2, 3]) ? $form_state->get('page_num') : 1;
    $form = $this->header($form, $form_state);
    switch ($step) {
      case 2:
        return $this->pageTwo($form, $form_state);

      case 3:
        return $this->pageThree($form, $form_state);

      default:
        return $this->pageOne($form, $form_state);
    }
  }

  /**
   * Returns the form header (fe. Page 1 of 2).
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form with the header.
   */
  private function header(array $form, FormStateInterface &$form_state) {
    if ($form_state->has('page_num')) {
      $current_step = $form_state->get('page_num');
    }
    else {
      $current_step = 1;
      $form_state->set('page_num', $current_step);
    }

    $stages = [
      1 => ['#markup' => '1. Page one'],
      2 => ['#markup' => '2. Page two'],
      3 => ['#markup' => '3. Page three'],
    ];

    $stages[$current_step]['#wrapper_attributes'] = ['class' => ['title']];
    $form['header'] = [
      '#type' => 'fieldset',
      '#title' => '',
    ];
    $form['header']['items'] = [
      '#theme' => 'item_list',
      '#items' => $stages,
    ];

    return $form;
  }

  /**
   * Returns the form for page one.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  private function pageOne(array $form, FormStateInterface &$form_state) {
    drupal_set_message('form handler step 1 called by past_testhidden_form_multistep');
    $form['sample_property'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#default_value' => 'sample value',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['next'] = [
      '#type' => 'submit',
      '#value' => 'Next',
    ];
    return $form;
  }

  /**
   * Returns the form for page two.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  private function pageTwo(array $form, FormStateInterface &$form_state) {
    drupal_set_message('form handler step 2 called by past_testhidden_form_multistep');
    $form['sample_property_2'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#default_value' => 'sample value 2',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['next'] = [
      '#type' => 'submit',
      '#value' => 'Next',
    ];
    $form['back'] = [
      '#type' => 'submit',
      '#value' => 'Back',
      // We won't bother validating the required fields, since they
      // have to come back to this page to submit anyway.
      '#limit_validation_errors' => [],
    ];
    return $form;
  }

  /**
   * Returns the form for page three.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  private function pageThree(array $form, FormStateInterface&$form_state) {
    drupal_set_message('form handler step 3 called by past_testhidden_form_multistep');
    $form['sample_property_3'] = [
      '#type' => 'textfield',
      '#title' => t('Sample Property'),
      '#default_value' => 'sample value 3',
      '#description' => 'Please enter a dummy value.',
      '#size' => 20,
      '#maxlength' => 20,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    $form['back'] = [
      '#type' => 'submit',
      '#value' => 'Back',
      // We won't bother validating the required fields, since they
      // have to come back to this page to submit anyway.
      '#limit_validation_errors' => [],
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
    if (!$form_state->has('page_num')) {
      $form_state->set('page_num', 1);
    }
    drupal_set_message(new FormattableMarkup('global submit handler step @page_num called by @form_id',
      ['@page_num' => $form_state->get('page_num'), '@form_id' => $form['#form_id']]));
    $form_state->set('page_values', [$form_state->get('page_num') => $form_state->getValues()]);

    switch ($form_state->get('page_num')) {

      case 3:
        if ($form_state->getTriggeringElement()['#value'] != 'Back') {
          $form_state->set('complete', TRUE);
        }
        break;

      default:
        $multi_step = $form_state->get('multi_step');
        $multi_step['new_stage'] = $form_state->get('page_num') + 1;
        $form_state->set('multi_step', $multi_step);
        break;

    }

    if ($form_state->has('complete')) {
      return;
    }

    if ($form_state->getTriggeringElement()['#value'] == 'Back') {
      $multi_step = $form_state->get('multi_step');
      $multi_step['new_stage'] = $form_state->get('page_num') - 1;
      $form_state->set('multi_step', $multi_step);
    }

    if (!empty($form_state->get('page_values')[$form_state->get('multi_step')['new_stage']])) {
      $form_state->setValues($form_state->get('page_values')[$form_state->get('multi_step')['new_stage']]);
    }

    $form_state->set('page_num', $form_state->get('multi_step')['new_stage']);
    $form_state->setRebuild();
  }

}
