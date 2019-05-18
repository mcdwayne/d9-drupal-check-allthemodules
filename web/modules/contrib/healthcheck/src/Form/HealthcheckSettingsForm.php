<?php

namespace Drupal\healthcheck\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\healthcheck\Plugin\HealthcheckPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HealthcheckSettingsForm.
 */
class HealthcheckSettingsForm extends ConfigFormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Healthcheck plugin manager.
   *
   * @var \Drupal\healthcheck\Plugin\HealthcheckPluginManager
   */
  protected $checkPluginMgr;

  /**
   * The configuration key for Healthcheck settings.
   */
  const CONF_ID = 'healthcheck.settings';

  /**
   * Constructs a new HealthcheckSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              HealthcheckPluginManager $check_plugin_mgr) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->checkPluginMgr = $check_plugin_mgr;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.healthcheck_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONF_ID,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'healthcheck_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get(static::CONF_ID);

    $form['cron'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Background processing')
    ];

    $run_every = $config->get('run_every');
    if (empty($run_every)) {
      $run_every = 3600;
    }

    $form['cron']['run_every'] = [
      '#type' => 'select',
      '#title' => $this->t('Run every'),
      '#description' => $this->t('How often a new healthcheck is run.'),
      '#options' => [
        -1        => $this->t('Never run in the background'),
        3600      => $this->t('Hour'),
        86400     => $this->t('Day'),
        604800    => $this->t('Week'),
        2592000   => $this->t('30 days'),
        1         => $this->t('Cron run')],
      '#size' => 1,
      '#default_value' => $run_every,
    ];

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notifications')
    ];

    $when_to_notify = $config->get('when_to_notify');
    $form['notifications']['when_to_notify'] = [
      '#type' => 'radios',
      '#title' => $this->t('When to notify'),
      '#options' => [
        'never'  => $this->t('Do not send notifications'),
        'always' => $this->t('Notify whenever a healthcheck is run')
      ],
      '#description' => $this->t('If and when to send notifications when a new healthcheck is run.'),
      '#default_value' => empty($when_to_notify) ? 'always' : $when_to_notify,
    ];

    $site_email = $this->configFactory->get('system.site')->get('mail');
    $default_email = $config->get('email');
    $form['notifications']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('The email address to send Healthcheck reports.'),
      '#default_value' => empty($default_email) ? $site_email : $default_email,
      '#required' => TRUE,
    ];

    $form['report'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Report configuration')
    ];

    $all_categories = $this->checkPluginMgr->getTagsSelectList();
    $categories_default = $config->get('categories');

    $form['report']['categories'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('Select the categories of Healthchecks to run, some checks are in multiple categories.'),
      '#options' => $all_categories,
      '#default_value' => empty($categories_default) ? array_keys($all_categories) : $categories_default,
      '#weight' => '0',
    ];

    $form['report']['omit_checks'] = [
      '#type' => 'select',
      '#title' => $this->t('Omit checks'),
      '#description' => $this->t('Omit specific checks from the report.'),
      '#options' => $this->checkPluginMgr->getChecksSelectList(),
      '#size' => 5,
      '#weight' => '0',
      '#multiple' => TRUE,
      '#default_value' => $config->get('omit_checks'),
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
    parent::submitForm($form, $form_state);

    $this->config('healthcheck.settings')
      ->set('email', $form_state->getValue('email'))
      ->set('when_to_notify', $form_state->getValue('when_to_notify'))
      ->set('run_every', $form_state->getValue('run_every'))
      ->set('categories', $form_state->getValue('categories'))
      ->set('omit_checks', $form_state->getValue('omit_checks'))
      ->save();
  }

}
