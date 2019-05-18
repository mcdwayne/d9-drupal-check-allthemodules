<?php

namespace Drupal\memsource_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MemsourceConfigForm.
 *
 * @package Drupal\memsource_connector\Form
 */
class MemsourceConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'memsource_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config.memsource_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Memsource Connector authentication token'),
      '#maxlength' => 255,
      '#default_value' => $this->getMemsourceConfig()->get('connector_token'),
      '#description' => $this->t('This unique token is required by the Memsource Cloud to connect to your Drupal site.'),
      '#required' => TRUE,
    ];
    $form['list_status_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Import posts with the following status:'),
    ];
    $form['list_status_published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#default_value' => in_array('1', $this->getMemsourceConfig()->get('list_status')),
    ];
    $form['list_status_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unpublished'),
      '#default_value' => in_array('0', $this->getMemsourceConfig()->get('list_status')),
    ];
    $form['insert_status_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Set status for exported posts to:'),
    ];
    $form['insert_status'] = [
      '#type' => 'radios',
      '#default_value' => $this->getMemsourceConfig()->get('insert_status'),
      '#options' => array('1' => $this->t('Published'), '0' => $this->t('Unpublished')),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getMemsourceConfig()
      ->set('connector_token', $form_state->getValue('token'))
      ->save();
    $list_status = array();
    if ($form_state->getValue('list_status_published') != 0) {
      $list_status[] = '1';
    }
    if ($form_state->getValue('list_status_unpublished') != 0) {
      $list_status[] = '0';
    }
    $this->getMemsourceConfig()
      ->set('list_status', $list_status)
      ->save();
    $this->getMemsourceConfig()
      ->set('insert_status', $form_state->getValue('insert_status'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get the application config instance.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   A config instance.
   */
  private function getMemsourceConfig() {
    return $this->config('config.memsource_config');
  }

}
