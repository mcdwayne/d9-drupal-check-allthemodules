<?php

/**
 * @file
 * Contains Drupal\client_004\Form\Client004AdminForm.
 */

namespace Drupal\client_004\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Client004AdminForm.
 *
 * @package Drupal\client_004\Form
 */
class Client004AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'client_004.config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'client_004_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('client_004.config');

    $form['shop_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Shop URL'),
      '#description' => $this->t('URL of your 004 shop system.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('shop_url'),
    );
    $form['shop_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Shop name'),
      '#description' => $this->t('Name of your 004 shop system.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('shop_name'),
    );
    $form['image_resolution'] = array(
      '#type' => 'select',
      '#title' => $this->t('Image Resolution'),
      '#description' => $this->t('Specify the size of the image that should be retrieved. Not all resolutions may be available. The next larger version will be used.'),
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

    $this->config('client_004.config')
      ->set('shop_url', $form_state->getValue('shop_url'))
      ->save();

    $this->config('client_004.config')
      ->set('shop_name', $form_state->getValue('shop_name'))
      ->save();

    $this->config('client_004.config')
      ->set('image_resolution', $form_state->getValue('image_resolution'))
      ->save();
  }
}
