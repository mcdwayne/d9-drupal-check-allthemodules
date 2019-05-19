<?php

namespace Drupal\social_feeds_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class PintrestSocialFeedsBlockForm extends ConfigFormBase {

  /**
   * Constructor for PintrestSocialFeedsBlockForm.
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
    return 'pintrest_social_feeds_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.pintrest_social_feeds'];
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
    $value = $this->config('config.pintrest_social_feeds');

    // Facebook fieldset.
    $form['pintrest_social_feeds'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Pintrest Cridential'),
      '#weight' => 50,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['pintrest_social_feeds']['pintrest_user_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pintrest User Name'),
      '#default_value' => $value->get('pintrest_user_name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['pintrest_social_feeds']['pintrest_counts'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pintrest Count'),
      '#default_value' => $value->get('pintrest_counts'),
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

    $this->config('config.pintrest_social_feeds')
      ->set('pintrest_user_name', $form_state->getValue('pintrest_user_name'))
      ->set('pintrest_counts', $form_state->getValue('pintrest_counts'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
