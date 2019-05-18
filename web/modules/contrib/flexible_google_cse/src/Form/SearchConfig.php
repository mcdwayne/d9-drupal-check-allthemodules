<?php

namespace Drupal\flexible_google_cse\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class SearchConfig.
 */
class SearchConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexible_google_cse_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['flexible_google_cse.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $fgsConfig = $this->config('flexible_google_cse.settings');

    $form['gse_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GSE Key'),
      '#description' => $this->t('Google Custom Search Key'),
      '#maxlength' => 128,
      '#size' => 56,
      '#weight' => '0',
      '#default_value' => $fgsConfig->get('gse_key'),
    ];

    // Value of the Search Button.
    $form['submit_value'] = [
      '#type' => 'textfield',
      '#size' => 56,
      '#title' => $this->t('Search Button Value'),
      '#description' => $this->t('Configure Value of the search Button'),
      '#default_value' => $fgsConfig->get('submit_value'),
    ];

    // The page to display Result on.
    $form['search_url'] = [
      '#type' => 'textfield',
      '#size' => 56,
      '#title' => $this->t('Search Result URl'),
      '#description' => $this->t('Configure URl to display the search result on followed by /'),
      '#default_value' => $fgsConfig->get('search_url'),
    ];

    // Length of Search box.
    $form['textbox_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Search Box size'),
      '#description' => $this->t('Configure the text size of search input box'),
      '#default_value' => $fgsConfig->get('textbox_size'),
    ];

    $form['textbox_placeholder'] = [
      '#type' => 'textfield',
      '#size' => 56,
      '#title' => $this->t('Search Box placeholder text'),
      '#description' => $this->t('Configure the placeholder text of the search input box'),
      '#default_value' => $fgsConfig->get('textbox_placeholder'),
    ];

    $form['textbox_label'] = [
      '#type' => 'textfield',
      '#size' => 56,
      '#title' => $this->t('Search Box label text'),
      '#description' => $this->t('Configure the label text before search input box'),
      '#default_value' => $fgsConfig->get('textbox_label'),
    ];

    $form['textbox_description'] = [
      '#type' => 'textfield',
      '#size' => 56,
      '#title' => $this->t('Search Box description text'),
      '#description' => $this->t('Configure the descript text after search input box'),
      '#default_value' => $fgsConfig->get('textbox_description'),
    ];

    // Result display type.
    $form['result_layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Search Result Layout'),
      '#options' => [
        'gcse:search' => t('Result and search Box'),
        'gcse:searchresults-only' => t('Result Only'),
      ],
      '#default_value' => $fgsConfig->get('result_layout'),
    ];

    $form['result_empty_text'] = [
      '#type' => 'textfield',
      '#size' => 56,
      '#title' => $this->t('Search Result empty text'),
      '#description' => $this->t('The string to show when there are no results. This is passed as the noResultsString in Google CSE.'),
      '#default_value' => $fgsConfig->get('result_empty_text'),
    ];

    $form['result_size'] = [
      '#type' => 'textfield',
      '#size' => 12,
      '#title' => $this->t('Search Result size'),
      '#description' => $this->t('The maximum size of the result set. You may enter an integer, or one of the special values: large, small, filtered_cse. This is passed as the resultSetSize in Google CSE.'),
      '#default_value' => $fgsConfig->get('result_size'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $resultURL = $values['search_url'];

    parent::validateForm($form, $form_state);

    // Validate if the Search Result URL start with /
    if (!preg_match("/^\\/(.*)/i", trim($resultURL))) {
      $form_state->setErrorByName('search_url', $this->t('Invalid URL Alias provided. Please fix the issue before proceeding.'));
    }

    $result_size = trim($values['result_size']);
    $special_size = in_array($result_size, ['large', 'small', 'filtered_cse']);
    $numeric_size = is_numeric($result_size) && ((int) $result_size) > 0;
    if (!($special_size || $numeric_size)) {
      $form_state->setErrorByName('result_size', $this->t('Invalid Search Result size provided. Please provide an integer or one of the special values: large, small, filtered_cse'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // URL alias for the search result page.
    if ($values['search_url'] != '/fgcs_search') {
      $urlAlias = $this->createAlias($values['search_url']);
    }

    $fgsConfig_Settings = $this->config('flexible_google_cse.settings');
    $fgsConfig_Settings->set('gse_key', $values['gse_key']);
    $fgsConfig_Settings->set('textbox_size', $values['textbox_size']);
    $fgsConfig_Settings->set('textbox_placeholder', $values['textbox_placeholder']);
    $fgsConfig_Settings->set('textbox_label', $values['textbox_label']);
    $fgsConfig_Settings->set('textbox_description', $values['textbox_description']);
    $fgsConfig_Settings->set('submit_value', $values['submit_value']);
    $fgsConfig_Settings->set('search_url', $values['search_url']);
    $fgsConfig_Settings->set('result_layout', $values['result_layout']);
    $fgsConfig_Settings->set('result_empty_text', $values['result_empty_text']);
    $fgsConfig_Settings->set('result_size', $values['result_size']);

    $fgsConfig_Settings->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * A function to create A valid URL alias for the search result page.
   */
  public function createAlias($search_url) {
    $path = \Drupal::service('path.alias_storage')
      ->save("/fgs-search", $search_url, "en");
    return $path;
  }

}
