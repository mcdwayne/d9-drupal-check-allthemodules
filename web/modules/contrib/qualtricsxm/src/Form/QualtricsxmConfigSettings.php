<?php

namespace Drupal\qualtricsxm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * @file
 * Contains \Drupal\qualtricsxm\Form\QualtricsxmConfigSettings.
 */

/**
 * Class QualtricsxmConfigSettings.
 *
 * @package Drupal\qualtricsxm\Form
 */
class QualtricsxmConfigSettings extends ConfigFormBase {

  protected $configfactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->configfactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qualtricsxm_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('qualtricsxm.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['qualtricsxm.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['qualtricsxm_api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#required' => TRUE,
      '#default_value' => $this->configfactory->get('qualtricsxm.settings')->get('qualtricsxm_api_token'),
      '#description' => $this->t('Your API token. qualtricsxm_embed module requires API token.'),
    ];

    $form['qualtricsxm_datacenter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Datacenter ID'),
      '#required' => TRUE,
      '#default_value' => $this->configfactory->get('qualtricsxm.settings')->get('qualtricsxm_datacenter'),
      '#description' => $this->t('Your datacenter ID, e.g. au2.') . " <a href='https://api.qualtrics.com/docs/root-url' target='_blank'>" . "Find your DataCenter</a>",
    ];

    $form['qualtricsxm_organization_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization ID'),
      '#default_value' => $this->configfactory->get('qualtricsxm.settings')->get('qualtricsxm_organization_id'),
      '#description' => $this->t('Your datacenter.') . " <a href='https://api.qualtrics.com/docs/finding-qualtrics-ids' target='_blank'>" . "Find your Organization ID</a>",
    ];

    $form['qualtricsxm_secure_embed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Secure embedding'),
      '#default_value' => $this->configfactory->get('qualtricsxm.settings')->get('qualtricsxm_secure_embed'),
      '#description' => $this->t('Whether to use https:// for embedding a survey or not.'),
    ];

    $form['qualtricsxm_embed_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Embed width'),
      '#default_value' => $this->configfactory->get('qualtricsxm.settings')->get('qualtricsxm_embed_width'),
      '#description' => $this->t('Custom Qualtrics embed form width'),
    ];

    $form['qualtricsxm_embed_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Embed height'),
      '#default_value' => $this->configfactory->get('qualtricsxm.settings')->get('qualtricsxm_embed_height'),
      '#description' => $this->t('Custom Qualtrics embed form height'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
