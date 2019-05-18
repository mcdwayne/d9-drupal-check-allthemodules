<?php

namespace Drupal\revive_adserver\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\revive_adserver\InvocationMethodServiceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Revive adserver widget.
 *
 * @FieldWidget(
 *   id = "revive_adserver_zone",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "revive_adserver_zone"
 *   }
 * )
 */
class ReviveWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Invocation Method Manager.
   *
   * @var \Drupal\revive_adserver\InvocationMethodServiceManager
   */
  protected $invocationMethodManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, ConfigFactoryInterface $configFactory, AccountInterface $current_user, InvocationMethodServiceManager $invocationMethodServiceManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, []);
    $this->configFactory = $configFactory;
    $this->currentUser = $current_user;
    $this->invocationMethodManager = $invocationMethodServiceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('plugin.manager.revive_adserver.invocation_method_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $zones = $this->invocationMethodManager->getZonesOptionList();
    $enabled_zones = $this->getSetting('enabled_zones');
    // Only filter, if there were enabled zones specified.
    if (!empty($enabled_zones)) {
      foreach ($zones as $key => $zone) {
        if (!in_array($key, $enabled_zones)) {
          unset($zones[$key]);
        }
      }
    }

    $element['#theme_wrappers'][] = 'fieldset';
    $element['zone_id'] = [
      '#type' => 'number',
      '#title' => t('Zone'),
      '#description' => t('The Revive Adserver Zone Id.'),
      '#default_value' => isset($items->zone_id) ? $items->zone_id : NULL,
      '#access' => $this->currentUser->hasPermission('use revive_adserver field'),
      '#required' => TRUE,
    ];
    // If zones are available, transform number field into a select field.
    if (!empty($zones)) {
      $element['zone_id']['#type'] = 'select';
      $element['zone_id']['#options'] = $zones;
    }

    if ($this->getFieldSetting('invocation_method_per_entity')) {
      $invocation_methods = $this->invocationMethodManager->getInvocationMethodOptionList();
      $methods = $this->getSetting('invocation_methods');
      // Filter the invocation methods, by the whitelisted ones in the field widget.
      // Only filter, if there was a whitelist specified.
      if (!empty($methods)) {
        foreach ($invocation_methods as $key => $method) {
          if (!in_array($key, $methods)) {
            unset($invocation_methods[$key]);
          }
        }
      }
      $element['invocation_method'] = [
        '#type' => 'select',
        '#title' => $this->t('Invocation method'),
        '#description' => $this->t('Banner invocation method. How will the ads be displayed.'),
        '#default_value' => isset($items->invocation_method) ? $items->invocation_method : '',
        '#access' => $this->currentUser->hasPermission('use revive_adserver field'),
        '#options' => $invocation_methods,
        '#required' => TRUE,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'enabled_zones' => [],
        'invocation_methods' => [],
      ] + parent::defaultSettings();
  }

  /**
   * @inheritdoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    // Add whitelist options, to allow only specific zones to be selectable
    // in the entity form.
    $element['enabled_zones'] = [
      '#type' => 'select',
      '#title' => $this->t('Enabled zones'),
      '#description' => $this->t('Whitelist zones, that will be able to select in the entity form. Otherwise all zones will be selectable.'),
      '#default_value' => $this->getSetting('enabled_zones'),
      '#options' => $this->invocationMethodManager->getZonesOptionList(),
      '#multiple' => TRUE,
    ];

    // Add whitelist options only, when they are allowed to be specified per entity.
    if ($this->getFieldSetting('invocation_method_per_entity')) {
      $element['invocation_methods'] = [
        '#type' => 'select',
        '#title' => $this->t('Invocation methods'),
        '#description' => $this->t('Whitelist invocation methods, that will be possible to select in the entity form.'),
        '#default_value' => $this->getSetting('invocation_methods'),
        '#options' => $this->invocationMethodManager->getInvocationMethodOptionList(),
        '#multiple' => TRUE,
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // Show the enabled zones summary.
    if (!empty($this->getSetting('enabled_zones'))) {
      $this->getSetting('enabled_zones');
      $zone_options = $this->invocationMethodManager->getZonesOptionList();
      $zones = [];
      foreach ($this->getSetting('enabled_zones') as $zone_id) {
        $zones[] = $zone_options[$zone_id];
      }
      $summary[] = $this->t('Selectable zones: @zones', ['@zones' => implode(', ', $zones)]);
    }
    else {
      $summary[] = $this->t('All zones are selectable.');
    }

    // Show the invocation method options summary.
    if ($this->getFieldSetting('invocation_method_per_entity')) {
      // Use invocation method label.
      $invocation_method_options = $this->invocationMethodManager->getInvocationMethodOptionList();
      $methods = $this->getSetting('invocation_methods');
      $invocation_methods = [];
      foreach ($methods as $method) {
        $invocation_methods[] = $invocation_method_options[$method];
      }

      $summary[] = $this->t('Available methods: @invocation_methods', ['@invocation_methods' => implode(', ', $invocation_methods)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (empty($value['zone_id'])) {
        unset($values[$key]['zone_id']);
      }
      if (empty($value['invocation_method'])) {
        unset($values[$key]['invocation_method']);
      }
    }

    return $values;
  }

}
