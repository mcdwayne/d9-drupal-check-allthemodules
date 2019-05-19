<?php

namespace Drupal\webform_permissions_by_term\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 *
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_permissions_by_term_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_permissions_by_term.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('webform_permissions_by_term.settings.webform_permissions_by_term_vocab');

    $form = parent::buildForm($form, $form_state);

    $vocabularies = Vocabulary::loadMultiple();
    $vocab_options = [];

    if (!empty($vocabularies)) {

      foreach ($vocabularies as $vocab => $settings) {
        $vocab_options[$settings->get('vid')] = $settings->get('name');
      }

      $form['webform_permissions_by_term_vocab'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Vocabulary'),
        '#options' => $vocab_options,
        '#default_value' => $config->get('value'),
      ];

    }
    else {
      drupal_set_message(t('There are no Taxonomy Vocabularies defined in this Drupal instance. Please define one to use this module.'));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    \Drupal::configFactory()
      ->getEditable('webform_permissions_by_term.settings.webform_permissions_by_term_vocab')
      ->set('value', $form_state->getValue('webform_permissions_by_term_vocab'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
