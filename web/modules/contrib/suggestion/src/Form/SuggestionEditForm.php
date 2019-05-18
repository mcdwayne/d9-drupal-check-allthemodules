<?php

namespace Drupal\suggestion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\suggestion\SuggestionHelper;
use Drupal\suggestion\SuggestionStorage;

/**
 * Suggestion indexing form.
 */
class SuggestionEditForm extends FormBase {

  /**
   * The suggestion edit form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   * @param string $ngram
   *   The ngram that is being edited.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ngram = '') {
    if (!$ngram) {
      return $form;
    }
    $obj = SuggestionStorage::getSuggestion($ngram);

    $form['ngram'] = [
      '#type'  => 'value',
      '#value' => $obj->ngram,
    ];
    $form['atoms'] = [
      '#type'  => 'value',
      '#value' => $obj->atoms,
    ];
    $form['density'] = [
      '#type'  => 'value',
      '#value' => $obj->density,
    ];
    $form['ngram_txt'] = [
      '#markup' => $this->t('Suggestion: @ngram', ['@ngram' => $obj->ngram]),
      '#suffix' => '<br />',
      '#weight' => 10,
    ];
    $form['atoms_txt'] = [
      '#markup' => $this->t('Words: @atoms', ['@atoms' => $obj->atoms]),
      '#suffix' => '<br />',
      '#weight' => 20,
    ];
    $form['density_txt'] = [
      '#markup' => $this->t('Score: @density', ['@density' => $obj->density]),
      '#weight' => 30,
    ];
    $form['qty'] = [
      '#title'         => $this->t('Quantity'),
      '#type'          => 'textfield',
      '#default_value' => $obj->qty,
      '#required'      => TRUE,
      '#weight'        => 40,
    ];
    $form['src'] = [
      '#title'         => $this->t('Source'),
      '#type'          => 'select',
      '#options'       => SuggestionStorage::getSrcOptions(),
      '#default_value' => SuggestionHelper::srcBits($obj->src),
      '#multiple'      => TRUE,
      '#required'      => TRUE,
      '#weight'        => 50,
    ];
    $form['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Submit'),
      '#weight' => 100,
    ];
    return $form;
  }

  /**
   * The form ID.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'suggestion_edit';
  }

  /**
   * AJAX callback for the indexing form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $src = 0;

    foreach ((array) $form_state->getValue('src') as $bit) {
      $src |= intval($bit);
    }
    $key = ['ngram' => $form_state->getValue('ngram')];
    $fields = [
      'atoms'   => $form_state->getValue('atoms'),
      'density' => SuggestionHelper::calculateDensity($src, $form_state->getValue('atoms'), $form_state->getValue('qty')),
      'qty'     => $form_state->getValue('qty'),
      'src'     => $src,
    ];
    SuggestionStorage::mergeSuggestion($key, $fields);

    drupal_set_message($this->t('Updated: &ldquo;@ngram&rdquo;', ['@ngram' => $form_state->getValue('ngram')]));
  }

  /**
   * Validation function for the suggestion edit form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric(trim($form_state->getValue('qty')))) {
      $form_state->setErrorByName('qty', $this->t('The quantity must have a numeric value'));
    }
    if (!count((array) $form_state->getValue('src'))) {
      $form_state->setErrorByName('src', $this->t('The source must have a value.'));
    }
    elseif (isset($form_state->getValue('src')[0]) && count((array) $form_state->getValue('src')) > 1) {
      $form_state->setErrorByName('src', $this->t('The disabled option cannot be combined with other options.'));
    }
  }

}
