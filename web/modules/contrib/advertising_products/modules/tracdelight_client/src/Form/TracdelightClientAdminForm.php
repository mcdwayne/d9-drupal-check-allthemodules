<?php

/**
 * @file
 * Contains Drupal\tracdelight_client\Form\TracdelightClientAdminForm.
 */

namespace Drupal\tracdelight_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TracdelightClientAdminForm.
 *
 * @package Drupal\tracdelight_client\Form
 */
class TracdelightClientAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tracdelight_client.config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tracdelight_client_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tracdelight_client.config');
    $form['access_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Access Key'),
      '#description' => $this->t('Access Key to use the tracdelight API'),
      '#maxlength' => 128,
      '#default_value' => $config->get('access_key'),
    );

    $form['api'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tracdelight API'),
      '#description' => $this->t('URL of the tracdelight API. Include the API version. Important: / at the end.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('api'),
    );

    $form['image_resolution'] = array(
      '#type' => 'select',
      '#title' => $this->t('Image Resolution'),
      '#description' => $this->t('Specify the size of the image that should be retrieved.'),
      '#options' => ['50' => '50', '55' => '55', '60' => '60', '70' => '70', '80' => '80',
                  '85' => '85', '90' => '90', '100' => '100', '110' => '110', '120' => '120',
                  '130' => '130', '140' => '140', '150' => '150', '160' => '160',
                  '170' => '170', '180' => '180', '190' => '190', '200' => '200',
                  '210' => '210', '220' => '220', '230' => '230', '240' => '240',
                  '250' => '250', '300' => '300', '350' => '350', '400' => '400',
                  '425' => '425', '440' => '440', '450' => '450', '500' => '500',
                  '550' => '550', '600' => '600'],
      '#default_value' => $config->get('image_resolution'),
    );

    $vocabularies_options = array(0 => $this->t('None'));
    $field_map = \Drupal::entityManager()->getFieldMap();
    $term_field_map = $field_map['taxonomy_term'];
    if (isset($term_field_map['field_original_id'])) {
      $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
      foreach ($vocabularies as $id => $entity) {
        if (isset($term_field_map['field_original_id']['bundles'][$entity->id()])) {
          $vocabularies_options[$entity->id()] = $entity->label();
        }
      }
    }

    $form['vocabulary'] = array(
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#description' => $this->t('Choose the Drupal vocabulary that tracdelight should add its category terms to. The vocabulary needs to have a field "original_id". Terms will be added automatically.'),
      '#options' => $vocabularies_options,
      '#default_value' => $config->get('vocabulary'),
    );

    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $this->config('tracdelight_client.config')
      ->set('access_key', $form_state->getValue('access_key'))
      ->save();

    $this->config('tracdelight_client.config')
      ->set('api', $form_state->getValue('api'))
      ->save();

    $this->config('tracdelight_client.config')
      ->set('image_resolution', $form_state->getValue('image_resolution'))
      ->save();

    $this->config('tracdelight_client.config')
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->save();
  }
}
