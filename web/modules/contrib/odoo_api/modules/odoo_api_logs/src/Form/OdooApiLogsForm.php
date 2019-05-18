<?php

namespace Drupal\odoo_api_logs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OdooApiLogsForm.
 */
class OdooApiLogsForm extends ConfigFormBase {

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * Constructs a new OdooApiLogsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyValueFactoryInterface $key_value_factory) {
    parent::__construct($config_factory);
    $this->keyValueFactory = $key_value_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('keyvalue')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'odoo_api_logs.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odoo_api_logs_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('odoo_api_logs.config');

    $existing_tags = $this->keyValueFactory->get('odoo_api_logs_tags')
      ->get('processed_tags', []);

    $form['disabled_tags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Disable log for selected tags'),
      '#description' => $this->t('Choose the Odoo API call tags for which need to disable logs.'),
      '#default_value' => $config->get('disabled_tags') ?: [],
      '#options' => array_combine($existing_tags, $existing_tags),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('odoo_api_logs.config')
      ->set('disabled_tags', $form_state->getValue('disabled_tags'))
      ->save();
  }

}
