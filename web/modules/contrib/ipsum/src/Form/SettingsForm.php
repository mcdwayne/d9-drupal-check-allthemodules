<?php

namespace Drupal\ipsum\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ipsum\Plugin\Type\IpsumPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure statistics settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The ipsum plugin manager.
   *
   * @var \Drupal\ipsum\Plugin\Type\IpsumPluginManager
   */
  protected $ipsumManager;

  /**
   * Constructs a \Drupal\ipsum\Form\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\ipsum\Plugin\Type\IpsumPluginManager $ipsum_manager
   *   The ipsum plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandlerInterface $module_handler, IpsumPluginManager $ipsum_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->ipsumManager = $ipsum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.ipsum')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipsum_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ipsum.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get base form.
    $base_form = IpsumBaseForm::buildForm($this->configFactory, $this->ipsumManager);

    // Ipsum provider settings.
    $form['provider'] = array(
      '#type' => 'details',
      '#title' => $this->t('Providers'),
      '#open' => TRUE,
    );

    // Override title.
    $base_form['default_provider'] = $base_form['provider'];
    unset($base_form['provider']);
    $base_form['default_provider']['#title'] = $this->t('Default provider');
    $base_form['default_provider']['#description'] = $this->t('Select the default ipsum filler text provider.');

    // Merge forms.
    $form['provider'] += $base_form;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ipsum.settings');

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
