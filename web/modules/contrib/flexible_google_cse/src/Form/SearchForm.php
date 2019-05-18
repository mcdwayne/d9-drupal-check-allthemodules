<?php

namespace Drupal\flexible_google_cse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchForm.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexible_google_cse_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $fgsConfig = $this->config('flexible_google_cse.settings');

    $inputSize = $fgsConfig->get('textbox_size') ? $fgsConfig->get('textbox_size') : 15;
    $submitText = $fgsConfig->get('submit_value') ? $fgsConfig->get('submit_value') : $this->t('Search');
    $labelText = $fgsConfig->get('textbox_label') ? $fgsConfig->get('textbox_label') : $this->t('Search');
    $placeholderText = $fgsConfig->get('textbox_placeholder') ? $fgsConfig->get('textbox_placeholder') : '';
    $descriptionText = $fgsConfig->get('textbox_description') ? $fgsConfig->get('textbox_description') : '';
    $searchUrl = $fgsConfig->get('search_url') ? $fgsConfig->get('search_url') : '/fgs-search';

    $form['key-word'] = [
      '#type' => 'textfield',
      '#title' => $labelText,
      '#default' => '',
      '#description' => $descriptionText,
      '#attributes' => ['placeholder' => $placeholderText],
      '#maxlength' => 128,
      '#size' => $inputSize,
      '#weight' => '0',
    ];

    $form['Searchbutton'] = [
      '#type' => 'submit',
      '#title' => $this->t('Search'),
      '#value' => $submitText,
      '#weight' => '0',
      // And to get rid of "op" from URL.
      '#name' => '',
    ];

    $form['#action'] = $searchUrl;
    $form['#method'] = 'get';

    $form['#cache'] = [
      'tags' => ['config:flexible_google_cse.settings'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
  }

}
