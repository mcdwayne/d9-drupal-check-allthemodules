<?php

/**
 * @file
 * Contains Drupal\amazon\Form\SettingsForm.
 */

namespace Drupal\amazon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\amazon\Amazon;

/**
 * Class SettingsForm.
 *
 * @package Drupal\amazon_filter\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amazon.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amazon.settings');

    $description = '';
    $accessKey = Amazon::getAccessKey();
    $accessSecret = Amazon::getAccessSecret();
    if (empty($accessKey)) {
      $description = $this->t('You must sign up for an Amazon AWS account to use the Product Advertising Service. See the <a href=":url">AWS home page</a> for information and a registration form. Enter your Access Key ID here.', [':url' => 'https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key']);
    }
    else {
      $description = $this->t('The access key is set by another method and does not need to be entered here.');
    }
    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amazon AWS Access Key ID'),
      '#default_value' => $config->get('access_key'),
      '#description' => $description,
      '#disabled' => !empty($accessKey),
    ];

    if (empty($accessKey)) {
      $description = $this->t('You must sign up for an Amazon AWS account to use the Product Advertising Service. See the <a href=":url">AWS home page</a> for information and a registration form. Enter your Access Key Secret here.', [':url' => 'https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key']);
    }
    else {
      $description = $this->t('The access secret is set by another method and does not need to be entered here.');
    }
    $form['access_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amazon AWS Access Secret'),
      '#default_value' => $config->get('access_secret'),
      '#description' => $description,
      '#disabled' => !empty($accessSecret),
    ];

    $form['associates_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amazon Associates ID'),
      '#description' => $this->t('You must register as an <a href=":url">Associate with Amazon</a> before using this module.', [':url' => 'http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html']),
      '#default_value' => $config->get('associates_id'),
    ];

    $max_age = $config->get('default_max_age');
    if ($max_age == '') {
      // Defaults to 15 minutes.
      $max_age = '900';
    }
    $form['default_max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default max-age for retrieved information'),
      '#description' => $this->t('Number of seconds that the result from Amazon will be cached. This can be overridden by defining a different value in the text filter. Set to zero to disable caching by default.'),
      '#default_value' => $max_age,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty(Amazon::getAccessKey()) && empty($form_state->getValue('access_key'))) {
      $form_state->setErrorByName('access_key', $this->t('If you do not specify an access key here, you must use one of the other methods of providing this information, such as a server environment variable or a $config setting in settings.php.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('amazon.settings')
      ->set('access_key', $form_state->getValue('access_key'))
      ->set('access_secret', $form_state->getValue('access_secret'))
      ->set('associates_id', $form_state->getValue('associates_id'))
      ->set('default_max_age', $form_state->getValue('default_max_age'))
      ->save();
  }

}
