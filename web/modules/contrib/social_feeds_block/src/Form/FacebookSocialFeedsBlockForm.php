<?php

namespace Drupal\social_feeds_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class FacebookSocialFeedsBlockForm extends ConfigFormBase {

  /**
   * Constructor for SocialFeedsBlockForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {

    parent::__construct($config_factory);

  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'facebook_social_feeds_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.facebook_social_feeds_block'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $value = $this->config('config.facebook_social_feeds_block');

    // Facebook fieldset.
    $form['social_feeds_block_fb'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Facebook Cridential'),
      '#weight' => 50,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['social_feeds_block_fb']['fb_app_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page Name'),
      '#default_value' => $value->get('fb_app_name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['social_feeds_block_fb']['fb_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $value->get('fb_app_id'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['social_feeds_block_fb']['fb_secret_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Secret Key'),
      '#default_value' => $value->get('fb_secret_id'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['social_feeds_block_fb']['fb_no_feeds'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of Feeds'),
      '#default_value' => $value->get('fb_no_feeds'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('config.facebook_social_feeds_block')
      ->set('fb_app_name', $form_state->getValue('fb_app_name'))
      ->set('fb_app_id', $form_state->getValue('fb_app_id'))
      ->set('fb_secret_id', $form_state->getValue('fb_secret_id'))
      ->set('fb_no_feeds', $form_state->getValue('fb_no_feeds'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
