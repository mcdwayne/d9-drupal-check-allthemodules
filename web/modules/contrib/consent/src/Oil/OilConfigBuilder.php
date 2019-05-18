<?php

namespace Drupal\consent\Oil;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Configuration builder for the OIL.js framework.
 */
class OilConfigBuilder implements OilConfigBuilderInterface {

  use StringTranslationTrait;

  /**
   * The OIL.js framework version to be used.
   *
   * @var string
   */
  static protected $oilVersion = '1.2.5';

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The available parameters.
   *
   * @var array
   */
  protected $params;

  /**
   * Default parameter values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * OilConfigBuilder constructor.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(TypedConfigManagerInterface $typed_config_manager) {
    $this->typedConfigManager = $typed_config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function availableParameters() {
    if (!isset($this->params)) {
      $this->loadAvailableParams();
    }
    return $this->params;
  }

  protected function loadAvailableParams() {
    $this->params = $this->typedConfigManager->getDefinition('oil_config_params')['mapping'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigTag(array $values) {
    if (empty($values['publicPath'])) {
      $values['publicPath'] = $this->defaultPublicPath();
    }
    return [
      '#theme' => 'oil_config',
      '#config' => $this->filterDefaultValues($this->defaultValues(), $values),
      '#attached' => ['drupalSettings' => ['consent' => ['oil_src' => $this->scriptSource()]]],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configFormElements(array $values) {
    $elements = [];
    foreach ($this->availableParameters() as $param => $info) {
      $value = isset($values[$param]) ? $values[$param] : NULL;
      $elements[$param] = $this->buildFormElement($param, $info, $value);
    }
    return $elements;
  }

  /**
   * Helper function to build the form element.
   *
   * @param $param
   *   The param key.
   * @param array $info
   *   The schema info.
   * @param mixed $value
   *   (Optional) If known, the param value.
   *
   * @return array
   *   The form element.
   */
  protected function buildFormElement($param, array $info, $value = NULL) {
    $element = [
      '#type' => 'textfield',
      '#title' => $param . ': ' . $this->t($info['label']),
    ];
    if (isset($value) && !isset($info['mapping'])) {
      $element['#default_value'] = $value;
    }
    switch ($info['type']) {
      case 'boolean':
        $element['#type'] = 'checkbox';
        break;
      case 'integer':
        $element['#type'] = 'number';
        break;
      case 'mapping':
        $element['#type'] = 'fieldset';
        $element += [
          '#collapsible' => FALSE,
          '#collapsed' => FALSE,
        ];
        foreach ($info['mapping'] as $param => $info) {
          $child_value = isset($value[$param]) ? $value[$param] : NULL;
          $element[$param] = $this->buildFormElement($param, $info, $child_value);
        }
        break;
      default:
        $element['#maxlength'] = 4096;
    }
    switch ($param) {
      case 'publicPath':
        $element['#default_value'] = $this->defaultPublicPath();
        $element['#disabled'] = TRUE;
        break;
    }
    return $element;
  }

  /**
   * Filters out default values as they would apply anyway.
   *
   * The aim of this method is to reduce the size of the
   * Json configuration to be printed inside the Html document.
   *
   * @param array $default
   *   The known default values array.
   * @param array $values
   *   The values to be used as config.
   *
   * @return array
   *   A filtered array of $values.
   */
  protected function filterDefaultValues(array $default, array $values) {
    $filtered = [];

    foreach ($default as $param => $default_value) {
      if (!isset($values[$param])) {
        continue;
      }
      $value = $values[$param];
      if (($value === $default_value) || (empty($value) && empty($default_value))) {
        continue;
      }
      elseif (is_array($value)) {
        $filtered[$param] = $this->filterDefaultValues($default_value, $value);
        if (empty($filtered[$param])) {
          unset($filtered[$param]);
        }
      }
      else {
        $filtered[$param] = $value;
      }
    }

    if (empty($filtered['poi_activate_poi'])) {
      unset($filtered['poi_group_name'], $filtered['poi_hub_origin'], $filtered['poi_hub_path'], $filtered['poi_subscriber_set_cookie']);
    }

    return $filtered;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValues() {
    if (!isset($this->defaultValues)) {
      $this->createDefaultValues();
    }
    return $this->defaultValues;
  }

  /**
   * Creates the array of default values.
   */
  protected function createDefaultValues() {
    $this->defaultValues = [
      'preview_mode' => FALSE,
      'config_version' => NULL,
      'advanced_settings' => FALSE,
      'advanced_settings_purposes_default' => FALSE,
      'cookie_expires_in_days' => 31,
      'cpc_type' => 'standard',
      'default_to_optin' => FALSE,
      'gdpr_applies_globally' => TRUE,
      'iabVendorListUrl' => 'https://vendorlist.consensu.org/vendorlist.json',
      'locale_url' => NULL,
      'locale' => [
        'localeId' => NULL,
        'version' => NULL,
        'texts' => [
          'label_intro_heading' => NULL,
          'label_intro' => NULL,
          'label_button_yes' => NULL,
          'label_button_back' => NULL,
          'label_button_advanced_settings' => NULL,
          'label_cpc_heading' => NULL,
          'label_cpc_text' => NULL,
          'label_cpc_activate_all' => NULL,
          'label_cpc_deactivate_all' => NULL,
          'label_cpc_purpose_desc' => NULL,
          'label_cpc_purpose_optout_confirm_heading' => NULL,
          'label_cpc_purpose_optout_confirm_text' => NULL,
          'label_cpc_purpose_optout_confirm_proceed' => NULL,
          'label_cpc_purpose_optout_confirm_cancel' => NULL,
          'label_nocookie_head' => NULL,
          'label_nocookie_text' => NULL,
          'label_poi_group_list_heading' => NULL,
          'label_poi_group_list_text' => NULL,
          'label_third_party' => NULL,
        ],
      ],
      'persist_min_tracking' => TRUE,
      'poi_activate_poi' => FALSE,
      'poi_group_name' => NULL,
      'poi_hub_origin' => 'https://unpkg.com',
      'poi_hub_path' => '/@ideasio/oil.js@' . static::$oilVersion . '-SNAPSHOT/release/current/hub.html',
      'poi_subscriber_set_cookie' => TRUE,
      'publicPath' => 'https://unpkg.com/@ideasio/oil.js@' . static::$oilVersion . '-SNAPSHOT/release/current/',
      'require_optout_confirm' => FALSE,
      'show_limited_vendors_only' => FALSE,
      'theme' => 'light',
      'timeout' => 60,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultPublicPath() {
    return '/' . drupal_get_path('module', 'consent') . '/js/oil/' . static::$oilVersion . '/';
  }

  /**
   * {@inheritdoc}
   */
  public function scriptSource() {
    return $this->defaultPublicPath() . 'oil.' . static::$oilVersion . '-RELEASE.min.js?v=' . static::$oilVersion;
  }

}
