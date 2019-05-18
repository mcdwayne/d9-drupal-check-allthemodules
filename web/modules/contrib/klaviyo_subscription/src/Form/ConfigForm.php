<?php

namespace Drupal\klaviyo_subscription\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Class ConfigForm.
 *
 * @package Drupal\klaviyo_subscription\Form
 */
class ConfigForm extends ConfigFormBase {

  protected $transcoder;
  protected $keyRepo;

  /**
   * ConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory for parent.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
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
      'klaviyo_subscription.config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kl_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $config = $this->config('klaviyo_subscription.config');  
	  $form['kl_api'] = [
		  '#type' => 'textfield',
		  '#title' => $this->t('Klaviyo API Key'),
		  '#size' => 60,
		  '#maxlength' => 128,
		  '#required' => TRUE,
		  '#default_value' => $config->get('kl_api') ? $config->get('kl_api') : NULL
		];
		
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
	  $this->config('klaviyo_subscription.config')
        ->set('kl_api', $form_state->getValue('kl_api'))
        ->save(); 
  }

}
