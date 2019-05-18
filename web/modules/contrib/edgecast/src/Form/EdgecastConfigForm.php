<?php

namespace Drupal\edgecast\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EdgecastConfigForm.
 *
 * @package Drupal\edgecast\Form
 */
class EdgecastConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'edgecast.api',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edgecast_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('edgecast.api');
    $form['edgecast_customer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edgecast Account ID'),
      '#default_value' => $config->get('edgecast_customer'),
      '#required' => TRUE,
    ];
    $form['edgecast_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edgecast Token'),
      '#default_value' => $config->get('edgecast_token'),
      '#required' => TRUE,
    ];
    $form['edgecast_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Path'),
      '#default_value' => $config->get('edgecast_path'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('edgecast.api')
      ->set('edgecast_customer', $form_state->getValue('edgecast_customer'))
      ->set('edgecast_token', $form_state->getValue('edgecast_token'))
      ->set('edgecast_path', $form_state->getValue('edgecast_path'))
      ->save();
  }

}
