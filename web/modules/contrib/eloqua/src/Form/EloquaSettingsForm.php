<?php

/**
 * @file
 * Contains \Drupal\eloqua\Form\EloquaSettingsForm.
 */

namespace Drupal\Eloqua\Form;

use Drupal\Component\Utility\HTML;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Eloqua settings for this site.
 */
class EloquaSettingsForm extends ConfigFormBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The request_path condition.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath $requestPath
   */
  protected $requestPath;

  /**
   * The user_role condition.
   *
   * @var \Drupal\user\Plugin\Condition\UserRole $userRole
   */
  protected $userRole;

  /**
   * Creates a new EloquaSettingsForm.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the tracking scope UI.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExecutableManagerInterface $manager, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);
    $this->manager = $manager;
    $this->requestPath = $plugin_factory->createInstance('request_path');
    $this->userRole = $plugin_factory->createInstance('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eloqua_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eloqua.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Load our default configuration settings.
    $config = $this->config('eloqua.settings');

    $form['eloqua_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#description' => $this->t('General settings applicable to all Eloqua functionality.'),
      '#open' => TRUE,
    );
    $form['eloqua_settings']['site_identifier'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site Identifier'),
      '#description' => $this->t('The Eloqua Site ID for this web site. Required to include the Eloqua tracking code.'),
      '#required' => TRUE,
      '#size' => 20,
      '#maxlength' => 64,
      '#default_value' => $config->get('site_identifier'),
    );

    $form['tracking_scope'] = $this->buildTrackignScopeInterface([], $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Helper function for building the tracking scope UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with the tracking scope UI added in.
   */
  protected function buildTrackignScopeInterface(array $form, FormStateInterface $form_state) {
    $config = $this->config('eloqua.settings');

    $form['tracking_scope'] = array(
      '#type' => 'item',
      '#title' => $this->t('Tracking scope'),
      '#description' => $this->t('Configuration to include/exclude the Eloqua tracking code.'),
    );

    $form['tracking_scope_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking Scope Conditions'),
      '#title_display' => 'invisible',
      '#parents' => ['tracking_scope_tabs'],
      '#attached' => [
        'library' => [
          'eloqua/eloqua.admin',
        ],
      ],
    ];

    // Set the condition configuration.
    $this->requestPath->setConfiguration($config->get('request_path'));
    $this->userRole->setConfiguration($config->get('user_role'));

    // Build the request_path condition configuration form elements.
    $form += $this->requestPath->buildConfigurationForm($form, $form_state);
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
      $form['pages']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
      // Switch negate default value form boolean to integer.
      // Solves issue related to https://www.drupal.org/node/2450637.
      $form['pages']['negate']['#default_value'] = ($form['pages']['negate']['#default_value'] === FALSE) ? 0 : 1;
    }

    // Build the user_role condition configuration form elements.
    $form += $this->userRole->buildConfigurationForm($form, $form_state);
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->requestPath->submitConfigurationForm($form, $form_state);
    $this->userRole->submitConfigurationForm($form, $form_state);
    $this->config('eloqua.settings')
      ->set('site_identifier', Html::escape($form_state->getValue('site_identifier')))
      ->set('request_path', $this->requestPath->getConfiguration())
      ->set('user_role', $this->userRole->getConfiguration())
      ->save();

    parent::submitForm($form, $form_state);
  }
}