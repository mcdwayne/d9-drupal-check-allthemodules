<?php

namespace Drupal\Pardot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Pardot settings for this site.
 */
class PardotSettingsForm extends ConfigFormBase {

  /**
   * Configuration settings.
   */
  protected $settings;

  /**
   * @var \Drupal\system\Plugin\Condition\RequestPath $path_condition.
   */
  protected $path_condition;

  /**
   * @var \Drupal\user\Plugin\Condition\UserRole $user_role_condition.
   */
  protected $user_role_condition;


  /**
   * PardotSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExecutableManagerInterface $plugin_factory) {
    parent::__construct($config_factory);
    // Load from pardot.settings.yml.
    $this->settings = $this->config('pardot.settings');

    // Create condition plugins.
    $this->path_condition = $plugin_factory->createInstance('request_path');
    $this->user_role_condition = $plugin_factory->createInstance('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pardot_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('pardot.settings');
  }

  /**
   * Build Pardot Settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['pardot_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#description' => $this->t('General settings applicable to all Pardot functionality.'),
      '#open' => TRUE,
    );
    $form['pardot_settings']['account_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pardot Account ID'),
      '#description' => $this->t('The value shown in the Pardot demo script for piAId. eg. if the script has piAId = "1001"; this field should be 1001.'),
      '#required' => TRUE,
      '#size' => 20,
      '#maxlength' => 64,
      '#default_value' => $this->settings->get('account_id'),
    );
    $form['pardot_settings']['default_campaign_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default Pardot Campaign ID'),
      '#description' => $this->t('The value shown in the Pardot demo script for piCId. eg. if the script has piCId = "1001"; this field should be 1001.'),
      '#required' => TRUE,
      '#size' => 20,
      '#maxlength' => 64,
      '#default_value' => $this->settings->get('default_campaign_id'),
    );

    // Add tracking scope vertical tabs.
    $form['tracking_scope'] = array(
      '#type' => 'item',
      '#title' => $this->t('Tracking scope'),
      '#description' => $this->t('Configuration to include/exclude the Pardot tracking code. The tracking code, with default Campaign ID, will be added to the site paths according to this configuration. Additional campaigns can be added to override the default campaign on specific paths included within this configuration.'),
    );
    $form['tracking_scope_tabs'] = array(
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking Scope Conditions'),
      '#title_display' => 'invisible',
      '#default_tab' => 'pages',
    );

    // Set and build the request_path condition configuration form elements.
    $this->path_condition->setConfiguration($this->settings->get('path_condition'));
    $form += $this->path_condition->buildConfigurationForm($form, $form_state);
    if (isset($form['pages'])) {
      $form['pages']['pages'] = $form['pages'];
      $form['pages']['negate'] = $form['negate'];
      unset($form['pages']['#description']);
      unset($form['negate']);
      $form['pages']['#type'] = 'details';
      $form['pages']['#group'] = 'tracking_scope_tabs';
      $form['pages']['#title'] = $this->t('Pages');
      $form['pages']['negate']['#type'] = 'radios';
      $form['pages']['negate']['#title_display'] = 'invisible';
      $form['pages']['negate']['#options'] = array(
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      );
      $form['pages']['negate']['#default_value'] = (int) $form['pages']['negate']['#default_value'];
    }

    // Set and build the user_role condition configuration form elements.
    $this->user_role_condition->setConfiguration($this->settings->get('user_role_condition'));
    $form += $this->user_role_condition->buildConfigurationForm($form, $form_state);
    if (isset($form['roles'])) {
      $form['roles']['roles'] = $form['roles'];
      $form['roles']['negate'] = $form['negate'];
      unset($form['roles']['#description']);
      unset($form['negate']);
      $form['roles']['#type'] = 'details';
      $form['roles']['#group'] = 'tracking_scope_tabs';
      $form['roles']['#title'] = $this->t('Roles');
      $form['roles']['negate']['#type'] = 'value';
      $form['roles']['negate']['#default_value'] = FALSE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @Todo: replace custom validation with plugin validation, if core
    // issue gets included: https://www.drupal.org/node/2843992.
    // $this->path_condition->validateConfigurationForm($form, $form_state);

    // Validates path conditions to ensure they have leading forward slash.
    $paths = explode("\r\n", $form_state->getValue('pages'));
    foreach ($paths as $path) {
      if (empty($path) || $path === '<front>' || strpos($path, '/') === 0) {
        continue;
      }
      else {
        $message = $this->t('Paths require a leading forward slash when used with the Tracking Scope Pages setting.');
        $form_state->setErrorByName('pages', $message);
        return;
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit condition plugin configurations.
    $this->path_condition->submitConfigurationForm($form, $form_state);
    $this->user_role_condition->submitConfigurationForm($form, $form_state);

    // Save configuration to settings.
    $this->config('pardot.settings')
      ->set('account_id', Html::escape($form_state->getValue('account_id')))
      ->set('default_campaign_id', Html::escape($form_state->getValue('default_campaign_id')))
      ->set('path_condition', $this->path_condition->getConfiguration())
      ->set('user_role_condition', $this->user_role_condition->getConfiguration())
      ->save();

    parent::submitForm($form, $form_state);
  }
}
