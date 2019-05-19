<?php

namespace Drupal\simple_access_log;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure statistics settings for this site.
 */
class SimpleAccessLogSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\simple_access_log\StatisticsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_access_log_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_access_log.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_access_log.settings');

    $form['content'] = [
      '#type' => 'details',
      '#title' => t('Simple Access Log settings'),
      '#open' => TRUE,
    ];
    $form['content']['simple_access_log_do_not_log_0'] = [
      '#type' => 'checkbox',
      '#title' => t('Don`t log UID 0'),
      '#default_value' => $config->get('do_not_log_0'),
      '#description' => t('Do not log Anonymous page visits'),
    ];
    $form['content']['simple_access_log_do_not_log_1'] = [
      '#type' => 'checkbox',
      '#title' => t('Don`t log UID 1'),
      '#default_value' => $config->get('do_not_log_1'),
      '#description' => t('Do not log Super User (UID 1) page visits'),
    ];
    $form['content']['simple_access_log_do_not_log_admin'] = [
      '#type' => 'checkbox',
      '#title' => t('Don`t log Admin users'),
      '#default_value' => $config->get('do_not_log_admin'),
      '#description' => t('Do not log for users with "Administrator" role'),
    ];
    $form['content']['simple_access_log_not_admin_paths'] = [
      '#type' => 'checkbox',
      '#title' => t('Don`t log Admin paths'),
      '#default_value' => $config->get('not_admin_paths'),
      '#description' => t('Skip logging for any admin paths. i.e. those paths that start with "/admin/*".'),
    ];
    $form['content']['simple_access_log_respect_dnt'] = [
      '#type' => 'checkbox',
      '#title' => t('Respect Client`s Do Not Track requests'),
      '#default_value' => $config->get('respect_dnt'),
      '#description' => t('Skip logging for clients sending the Do Not Track header. Note: This is enabled by default on many browsers, and the user may not be aware or have explicitly chosen it.'),
    ];
    $options = [
      '9676800' => '4 Months (16 Weeks)',
      '2419200' => '4 Weeks',
      '1209600' => '2 Weeks',
      '604800' => '1 Week',
      '259200' => '3 Days',
      '86400' => '1 Day',
    ];
    // What if someone manually overrides the value?
    if (!in_array($config->get('delete_log_after'), array_keys($options))) {
      $options[$config->get('delete_log_after')] = $config->get('delete_log_after') . ' seconds';
    }
    $form['content']['simple_access_log_delete_log_after'] = [
      '#type' => 'select',
      '#title' => t('Log retention period'),
      '#empty_value' => '',
      '#default_value' => $config->get('delete_log_after'),
      '#options' => $options,
      '#description' => t('Length of time to keep access logs.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('simple_access_log.settings')
      ->set('do_not_log_0', $form_state->getValue('simple_access_log_do_not_log_0'))
      ->set('do_not_log_1', $form_state->getValue('simple_access_log_do_not_log_1'))
      ->set('do_not_log_admin', $form_state->getValue('simple_access_log_do_not_log_admin'))
      ->set('not_admin_paths', $form_state->getValue('simple_access_log_not_admin_paths'))
      ->set('delete_log_after', $form_state->getValue('simple_access_log_delete_log_after'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
