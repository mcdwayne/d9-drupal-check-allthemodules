<?php

namespace Drupal\commerce_billbee\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_billbee_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_billbee.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('commerce_billbee.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Billbee API key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#description' => $this->t('Fill in a random key here. When you create a shop connection in Bilbee, enter the same key in the shop connection settings on Bilbee as entered here.'),

    ];

    $form['shop_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Billbee internal shop id'),
      '#default_value' => $config->get('shop_id'),
      '#required' => FALSE,
      '#description' => $this->t('The "Interne Shop ID" which is visible after creating a shop on Billbee.de. Only required if you want realtime order synchronisation to Billbee.'),
    ];

    $all_image_fields = \Drupal::service('entity_field.manager')
      ->getFieldMapByFieldType('image');
    $available_image_fields = ['_none' => t('No image mapping')];
    if (isset($all_image_fields['commerce_product_variation'])) {
      foreach ($all_image_fields['commerce_product_variation'] as $field_id => $data) {
        $available_image_fields[$field_id] = $field_id;
      }
    }
    $form['image_field'] = [
      '#title' => $this->t('Image field mapping'),
      '#type' => 'select',
      '#options' => $available_image_fields,
      '#default_value' => $config->get('image_field'),
      '#description' => $this->t('All image fields defined on a product variation are available here. If you do not want to syncronise images, set to "No image mapping".'),
    ];

    $form['debug'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
      '#open' => false,
    ];

    $form['debug']['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging'),
      '#default_value' => $config->get('enable_logging'),
      '#required' => FALSE,
      '#description' => $this->t('Log every Billbee API call to standard Drupal logs. Only needed for debugging.'),
    ];

    $form['debug']['skip_authentication'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass authentication'),
      '#default_value' => $config->get('skip_authentication'),
      '#required' => FALSE,
      '#description' => $this->t('<strong>WARNING: For development purpose only! This will bypass authentication of the Billbee API, which exposes all customers, orders and products and allows everybody to change stock. DO NOT ENABLE THIS ON LIVE SITES!</strong>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('commerce_billbee.settings')
      ->set('api_key', $values['api_key'])
      ->set('shop_id', $values['shop_id'])
      ->set('image_field', $values['image_field'])
      ->set('enable_logging', $values['enable_logging'])
      ->set('skip_authentication', $values['skip_authentication'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
