<?php

namespace Drupal\prevent_term_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\taxonomy\Entity\Vocabulary;
/**
 * Configure prevent_term_delete settings for this site.
 */
class PreventTermDeleteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prevent_term_delete_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'prevent_term_delete.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('prevent_term_delete.settings');
    $vocabulary_types =  taxonomy_vocabulary_get_names();
    if (empty($vocabulary_types)) {
      return NULL;
    }
    $vocabularies = Vocabulary::loadMultiple();
    
    $options = array();
    foreach ($vocabularies as $vocabulary => $type) {
      $options[$vocabulary] = $type->get('name');
    }

    $form['vocabulary'] = array(
      '#title' => t('Vocabulary'),
      '#type' => 'checkboxes',
      '#description' => t('Check the vocabularies that you wish to add restriction on deletion'),
      '#options' => $options,
      '#default_value' => $config->get('vocabulary'),
    );

  
    $form['delete_button'] = array(
      '#title' => t('Show delete button'),
      '#type' => 'checkbox',
      '#description' => t('This option will show delete button in term delete form page, even when the term is associated with entites'),
      '#default_value' => $config->get('delete_button'),
    );

   
    $form['limit'] = array(
      '#title' => t('Number of entities list to show in term delete form'),
      '#type' => 'textfield',
      '#description' => t("Number of entities list to show in term delete form"),
      '#size' => 2,
      '#default_value' => $config->get('limit'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('prevent_term_delete.settings')
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->set('delete_button', $form_state->getValue('delete_button'))
      ->set('limit', $form_state->getValue('limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
