<?php

namespace Drupal\content_locker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_locker\ContentLockerPluginManager;

/**
 * Configures aggregator settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The content locker manager.
   *
   * @var \Drupal\content_locker\ContentLockerPluginManager
   */
  private $contentLockerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ContentLockerPluginManager $contentLockerManager) {
    parent::__construct($config_factory);

    $this->contentLockerManager = $contentLockerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.content_locker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_locker_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_locker.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_locker.settings');

    $form['basic_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic settings'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];

    $form['basic_settings']['skin'] = [
      '#type' => 'select',
      '#title' => $this->t('Content locker style'),
      '#options' => ['light' => 'Light'],
      '#default_value' => $config->get('basic.skin'),
      '#empty_value' => 0,
    ];

    $form['basic_settings']['ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load content via ajax'),
      '#default_value' => $config->get('basic.ajax'),
      '#description' => $this->t('If you choose "Use ajax" option, it\'s mean we remove hidden fields from render and return them via ajax.'),
    ];

    $form['basic_settings']['cookie'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cookie'),
      '#default_value' => $config->get('basic.cookie'),
    ];

    $form['basic_settings']['cookie_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie lifetime'),
      '#default_value' => $config->get('basic.cookie_lifetime'),
      '#states' => [
        'visible' => [
          ':input[name="basic_settings[cookie]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Plugins'),
      '#open' => TRUE,
    ];

    return parent::buildForm($this->addPluginSettings($form), $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('content_locker.settings')
      ->set('basic', $form_state->getValue('basic_settings'))->save();

    if ($plugins = $form_state->getValue('plugins')) {
      foreach ($plugins as $plugin_provider => $plugin_settings) {
        $this->configFactory->getEditable($plugin_provider . '.settings')
          ->setData($plugin_settings)->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Implements plugin settings.
   */
  protected function addPluginSettings($form) {
    if ($definitions = $this->contentLockerManager->getDefinitions()) {
      foreach ($definitions as $plugin_id => $plugin) {
        $instance = $this->contentLockerManager->createInstance($plugin_id);
        $form[$plugin['provider']] = [
          '#type' => 'details',
          '#title' => $this->t('@pluginType', ['@pluginType' => $plugin['label']]),
          '#group' => 'tabs',
          '#parents' => ['plugins', $plugin['provider']],
          '#open' => TRUE,
          '#tree' => TRUE,
        ];
        $form[$plugin['provider']] += $instance->settingsForm();
      }
    }
    else {
      $form['empty'] = [
        '#markup' => $this->t('There are no content locker plugins.'),
      ];
    }

    return $form;
  }

}
