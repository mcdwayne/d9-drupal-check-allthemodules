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

    $config = $this->config('hash_tag_make.hash_tag_make_settings');

    // Filter fieldset.
    $form['htm_search_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Search'),
      '#attributes' => [
        'class' => [
          'htm-search-fiedlset',
        ],
      ],
    ];

    // Filter search pattern element.
    $form['htm_search_fieldset']['search_pattern'] = [
      '#type' => 'textfield',
      '#title' => 'Search Pattern',
      '#required' => TRUE,
      '#description' => t('Provide your search pattern. Default is "/search/node?keys=" and "$term" is appended with this module.'),
      '#default_value' => $config->get('search_pattern') ?: '/search/node?keys=',
    ];

    $form['#tree'] = TRUE;

    $form['htm_language_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Languages'),
      '#prefix' => '<div id="htm-language-fiedlset">',
      '#suffix' => '</div>',
    ];

    // Filter search pattern element.
    $form['htm_language_fieldset']['language_count'] = [
      '#type' => 'number',
      '#title' => 'Number of Languages',
      '#required' => TRUE,
      '#description' => t('Provide the number of field items below. Save form to reflect changes.'),
      '#default_value' => $config->get('language_count') ?: 1,
    ];

    $form['htm_language_fieldset']['actions']['adjust_language_count'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply item count adjustment'),
      '#submit' => ['::adjustLanguageCount'],
      '#default_value' => $form['htm_language_fieldset']['language_count']['#default_value'],
      '#ajax' => [
        'callback' => '::adjustCountCallback',
        'wrapper' => 'htm-language-fieldset',
      ],
    ];

    for ($i = 0; $i < $config->get('language_count'); $i++) {

      $form['htm_language_fieldset']['language'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Unicode Language'),
        '#required' => FALSE,
        '#default_value' => $config->get('language')[$i] ?: '',
      ];

    }

    return parent::buildForm($form, $form_state);

  }

  public function adjustCountCallback(array &$form, FormStateInterface $form_state) {
    return $form['htm_language_fieldset'];
  }

  public function adjustLanguageCount(array &$form, FormStateInterface $form_state) {
    $form_state->set('language_count', $form['htm_language_fieldset']['language_count']['#default_value']);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configFactory->getEditable('hash_tag_make.hash_tag_make_settings')
      ->set('search_pattern', $values['htm_search_fieldset']['search_pattern'])
      ->set('language_count', $values['htm_language_fieldset']['language_count'])
      ->set('language', $values['htm_language_fieldset']['language'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
