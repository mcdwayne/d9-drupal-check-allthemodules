<?php
/**
 * @file
 * The base marketing automate tracking configure form.
 */

namespace Drupal\automate\Tracking;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides base tracking configuration form for marketing automation modules.
 *
 * @Todo: determine best scope for $config, $request_path_condition and $user_role_condition properties.
 * @Todo: create and use interface requiring $config, $request_path_condition and $user_role_condition properties.
 */
class AutomateTrackingForm extends ConfigFormBase {
  protected $settings;
  /**
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface request_path
   */
  protected $request_path_condition;
  /**
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface user_role
   */
  protected $user_role_condition;

  /**
   * Creates a new EloquaSettingsForm.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);

    $this->request_path_condition = $plugin_factory->createInstance('request_path');
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
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [''];
  }

  /**
   * Create tracking scope configuration form elements.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['tracking_scope'] = array(
      '#type' => 'item',
      '#title' => $this->t('Tracking scope'),
      '#description' => $this->t('Configuration to include/exclude the tracking code.'),
    );
    $form['tracking_scope_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking Scope Conditions'),
      '#title_display' => 'invisible',
      '#parents' => ['tracking_scope_tabs'],
    ];

    // Set the conditions configuration.
    $this->request_path_condition->setConfiguration($this->settings->get('request_path'));
    $this->user_role_condition->setConfiguration($this->settings->get('user_role'));

    // Build the request_path condition configuration form elements.
    $form += $this->request_path_condition->buildConfigurationForm($form, $form_state);

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
        $this->t('Include tracking code for the listed pages'),
        $this->t('Exclude tracking code from the listed pages'),
      ];
      $form['pages']['negate']['#default_value'] = $form['pages']['negate']['#default_value'] === FALSE ? 0 : 1;
    }

    // Build the user_role condition configuration form elements.
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
