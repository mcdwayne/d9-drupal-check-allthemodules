<?php

namespace Drupal\vuukle\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class VuukleConfigForm.
 *
 * @package Drupal\vuukle\Form
 */
class VuukleConfigForm extends ConfigFormBase {

  public $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->config = $this->config('vuukleconfig.setting');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vuukleconfig.setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vuukle_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_type = node_type_get_names();
    $form['vuukle_content_type'] = [
      '#type' => 'checkboxes',
      '#options' => $node_type,
      '#default_value' => $this->config->get('vuukle_content_type'),
      '#title' => $this->t('Select content type for which you want vuukle enable'),
    ];
    $form['rating_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rating text'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('rating_text'),
    ];
    $form['comment_text_0'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comment text one'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('comment_text_0'),
    ];
    $form['comment_text_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comment text two'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('comment_text_1'),
    ];
    $form['comment_text_multi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comment Text Multi'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('comment_text_multi'),
    ];
    $form['ga_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Analytics code'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('ga_code'),
    ];
    $form['vuukle_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vuukle api key'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('vuukle_api_key'),
    ];
    $form['vuukle_col_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vuukle Col Code'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('vuukle_col_code'),
    ];
    $form['stories_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vuukle Stories title'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $this->config->get('stories_title'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config->set('rating_text', $form_state->getValue('rating_text'))->save();
    $this->config->set('comment_text_0', $form_state->getValue('comment_text_0'))->save();
    $this->config->set('comment_text_1', $form_state->getValue('comment_text_1'))->save();
    $this->config->set('comment_text_multi', $form_state->getValue('comment_text_multi'))->save();
    $this->config->set('ga_code', $form_state->getValue('ga_code'))->save();
    $this->config->set('vuukle_col_code', $form_state->getValue('vuukle_col_code'))->save();
    $this->config->set('vuukle_api_key', $form_state->getValue('vuukle_api_key'))->save();
    $this->config->set('vuukle_content_type', $form_state->getValue('vuukle_content_type'))->save();
    $this->config->set('stories_title', $form_state->getValue('stories_title'))->save();
    parent::submitForm($form, $form_state);
  }

}
