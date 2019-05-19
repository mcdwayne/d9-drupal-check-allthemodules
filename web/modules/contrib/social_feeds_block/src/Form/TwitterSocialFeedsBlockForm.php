<?php

namespace Drupal\social_feeds_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class TwitterSocialFeedsBlockForm extends ConfigFormBase {

  /**
   * Constructor for TwitterSocialFeedsBlockForm.
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
    return 'twitter_social_feeds_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.twitter_social_feeds'];
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
    $value = $this->config('config.twitter_social_feeds');

    // Facebook fieldset.
    $form['twitter_social_feeds'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Twitter Cridential'),
      '#weight' => 50,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['twitter_social_feeds']['tw_consumer_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Consumer Key'),
      '#default_value' => $value->get('tw_consumer_key'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['twitter_social_feeds']['tw_consumer_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Consumer Secret'),
      '#default_value' => $value->get('tw_consumer_secret'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['twitter_social_feeds']['tw_user_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter User Name'),
      '#default_value' => $value->get('tw_user_name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['twitter_social_feeds']['tw_counts'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tweetes Count'),
      '#default_value' => $value->get('tw_counts'),
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

    $this->config('config.twitter_social_feeds')
      ->set('tw_consumer_key', $form_state->getValue('tw_consumer_key'))
      ->set('tw_consumer_secret', $form_state->getValue('tw_consumer_secret'))
      ->set('tw_user_name', $form_state->getValue('tw_user_name'))
      ->set('tw_counts', $form_state->getValue('tw_counts'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
