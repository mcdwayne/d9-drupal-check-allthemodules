<?php

/**
 * Drupal\hash_tag_make\Form\HashTagMakeForm.
 */

namespace Drupal\hash_tag_make\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines our form class.
 */
class HashTagMakeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hash_tag_make_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hash_tag_make.hash_tag_make_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // get form config.
    $config = $this->config('hash_tag_make.hash_tag_make_settings');

    // Set our count. Used in for loop below.
    if (!empty($form_state->getValue(['htm_script_fieldset', 'script_count']))) {
      // Check if $form_state has count value.
      $defaultCount = $form_state->getValue(['htm_script_fieldset', 'script_count']);
    }
    else {
      // Get config count value.
      $defaultCount = $config->get('script_count');
    }

    // Allow multiple field items.
    $form['#tree'] = TRUE;

    // Fieldset item.
    $form['htm_search_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Search'),
      '#prefix' => '<div id="htm-search-fiedlset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Filter search pattern item.
    $form['htm_search_fieldset']['search_pattern'] = [
      '#type' => 'textfield',
      '#title' => 'Search Pattern',
      '#required' => TRUE,
      '#description' => t('Provide your search pattern. Default is "/search/node?keys=" and "#term" is appended with this module.'),
      '#default_value' => $config->get('search_pattern') ?: '/search/node?keys=',
    ];

    // Fieldset item.
    $form['htm_script_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Scripts'),
      '#prefix' => '<div id="htm-script-fiedlset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Script count item.
    $form['htm_script_fieldset']['script_count'] = [
      '#type' => 'number',
      '#title' => 'Number',
      '#required' => TRUE,
      '#description' => t('Provide the number of field items below.'),
      '#default_value' => $config->get('script_count') ?: 1,
    ];

    // Rebuild submit item.
    $form['htm_script_fieldset']['rebuild'] = [
      '#type' => 'submit',
      '#value' => 'Apply number',
      '#submit' => ['::rebuildFormSubmit'],
      '#ajax' => [
        'callback' => '::scriptCallback',
        'wrapper' => 'htm-script-fiedlset-wrapper',
      ],
    ];

    $form['htm_script_fieldset']['spacer'] = [
      '#type' => 'item',
      '#markup' => $this->t('<br /><hr /><br />'),
    ];

    // Loop through our count and create form items.
    for ($i = 0; $i < $defaultCount; $i++) {

      $form['htm_script_fieldset']['script'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Unicode Script'),
        '#required' => FALSE,
        '#default_value' => isset($config->get('script')[$i]) ? $config->get('script')[$i] : '',
      ];

    }

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function scriptCallback(array &$form, FormStateInterface $form_state) {
    return $form['htm_script_fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildFormSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->configFactory->getEditable('hash_tag_make.hash_tag_make_settings')
      ->set('search_pattern', $values['htm_search_fieldset']['search_pattern'])
      ->set('script_count', $values['htm_script_fieldset']['script_count'])
      ->set('script', $values['htm_script_fieldset']['script'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
