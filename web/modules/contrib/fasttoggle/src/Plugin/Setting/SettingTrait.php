<?php
/**
 * @file
 * Fasttoggle Object List of Values Setting
 */

namespace Drupal\fasttoggle\Plugin\Setting;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemList;
use Drupal\fasttoggle\Plugin\SettingGroup\AbstractSettingGroup;
use Drupal\field\Entity\FieldConfig;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;

require_once "SettingInterface.php";

/**
 * Abstract interface for settings.
 */
Trait SettingTrait {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->trait_constructor($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Allow access to the trait constructor if the setting also implements one.
   */
  public function trait_constructor(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $settingGroupManager = \Drupal::service('plugin.manager.fasttoggle.setting_group');
    $this->group = $settingGroupManager->createInstance($plugin_definition['group']);
    $groupDefinition = $this->group->getPluginDefinition();

    $objectManager = \Drupal::service('plugin.manager.fasttoggle.setting_object');
    $this->object = $objectManager->createInstance($groupDefinition['entityType']);
  }

  /**
   * Retrieve the object type that can be modified by this setting.
   *
   * @return string
   *   The machine name for the object type being modified by this setting.
   */
  public function __get($name) {
    // Simple member
    if (isset($this->{$name})) {
      return $this->{$name};
    }

    // Annotation
    $definition = $this->getPluginDefinition();
    if (isset($definition[$name])) {
      return $definition[$name];
    }

    // Specific getter function
    $functionName = "get_${name}";
    if (is_callable([$this, $functionName])) {
      return $this->{$functionName}();
    }

    // Unmatched
    $trace = debug_backtrace();
    trigger_error(
      'Undefined property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_ERROR);
    return NULL;
  }

  /**
   * Get labels (fallback function if a template is used (eg roles)
   *
   * return array
   *   A list of attributes that can be modified with this setting.
   */
  public function get_labels() {
    $attributes = [];
    $template_array = $this->label_template;

    foreach ($this->attributes as $value => $display) {
      $attributes[$value] = [];
      foreach ($template_array as $label_type => $labels) {
        $attributes[$value][$label_type] = [];
        foreach ($labels as $state => $template) {
          $attributes[$value][$label_type][$state] = sprintf($template, $display);
        }
      }
    }

    return $attributes;
  }

  /**
   * Set a custom field name (eg fields on nodes).
   */
  function setField($field) {
    if ($field) {
      $this->field = $field;
      $this->name = $field->getName();
    }
    else {
      unset($this->field);
      unset($this->name);
    }
  }
  /**
   * Retrieve the FieldItem for a setting.
   */
  function get_field() {
    if (is_array($this->object)) {
      $result = $this->object[$this->name];
    }
    else {
      $result = $this->object->{$this->name};
    }

    return $result;
  }

  /**
   * Retrieve the current value of the setting.
   *
   * @return string
   *   The current key matching getHumanReadableValueList / getValueList.
   */
  function get_value($instance = '') {
    $field = $this->get_field();
    return $field->get($instance == '' ? 0 : $instance)->value;
  }

  /**
   * Modify the setting.
   *
   * @param string instance
   *   The instance to modify.
   * @param mixed newValue
   *   The new value to save
   *
   * @return \Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface
   *   The related object, so you can chain a call to its the save method.
   */
  function set_value($instance, $newValue) {
    if (is_array($this->object)) {
      $ref = &$this->object[$this->name];
    }
    else {
      $ref = &$this->object->{$this->name};
    }

    // $ref = $ref->get($instance == '' ? 0 : $instance)->value;

    $ref->setValue($newValue);
  }

  /**
   * Move to the next setting value.
   *
   * @return \Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface
   *   The related object, so you can chain a call to its the save method.
   */
  public function nextValue($instance) {
    $values = $this->getValueList();
    $current = $this->get_value($instance);
    $useNext = FALSE;
    foreach (array_keys($values) as $key) {
      if ($useNext) {
        $this->set_value($instance, $key);
        return $this;
      }

      if ($key == $current) {
        $useNext = TRUE;
      }
    }

    // Return the first value or the default.
    if ($useNext) {
      reset($values);
      $newValue = key($values);
    }
    else {
      $newValue = $this->getDefault();
    }

    $this->set_value($instance, $newValue);
    return $this;
  }

  /**
   * Move to the previous setting value and save it.
   *
   * (Allows some widget to implement forward and back buttons if desired).
   *
   * @param string $attribute
   *    The name of the particular attribute being toggled.
   *
   * @return mixed
   *   The array key for the new value.
   */
  public function previousValue($instance) {
    $values = $this->getValueList();
    $current = $this->get_value($instance);
    $previousKey = NULL;
    $newKey = NULL;

    foreach (array_keys($values) as $key) {
      if ($key == $current) {
        if (isNull($previousKey)) {
          // Return the last key.
          end($values);
          $this->set_value($instance, current($values));
        }
        else {
          $newKey = $previousKey;
        }
      }
    }

    // If not found use the default.
    if (isNull($newKey)) {
      $newKey = $this->getDefault();
    }

    $this->set_value($instance, $newKey);
    return $this;
  }

  /**
   * Get a plain text list of human readable labels for the setting, in the
   * order used.
   *
   * This allows human readable labels to be sorted in non-alphabetical order.
   * Note that the widget object may use this or an attribute of the value
   * itself to render an icon, an ajax link or something else.
   *
   * @return array
   *   An array of human readable values, in the order they will appear when
   *   stepping through them.
   */
  public function getHumanReadableValueList() {
    $labels = $this->labels;

    $config = $this->config('fasttoggle.settings');
    $label_type = $config->get('label_type');
    return $labels[$label_type];
  }

  /**
   * Get a list of actual values for the setting, in the order used.
   *
   * Keys should match those returned for the list of human readable labels.
   *
   * @return array
   *   An array of the actual values for the field, with keys matching those
   *   returned by getHumanReadableValueList.
   */
  public function getValueList() {
    $labels = $this->labels;
    return array_keys($labels[FASTTOGGLE_LABEL_ACTION]);
  }

  /**
   * Check write access at every level.
   *
   * @return bool
   *   Whether the current user may modify the setting if they can modify the
   *   object.
   */
  public function mayEditSetting() {
    if (!$this->mayEditEntity()) {
      return AccessResult::forbidden();
    }
  }

  /**
   * Write access check.
   *
   * @param mixed $object
   *   The object being modified.
   *
   * @return bool
   *   Whether the current user may modify the setting if they can modify the
   *   object.
   */
  public function mayEdit() {

    return $this->mayEditEntity()
      ->andIf($this->mayEditGroup())
      ->andIf($this->mayEditSetting());
  }

  /**
   * Keys for config settings.
   *
   * @return string
   *   The key to use for config settings for this object.
   */
  public function configSettingKeys() {
    $keys = [];
    $objectGroup = $this->group->pluginId;
    foreach ($this->attributes as $attribute => $title) {
      $keys[] = "{$objectGroup}_{$attribute}";
    }
    return $keys;
  }

  /**
   * Return the sitewide form element for this setting.
   *
   * @return array
   *   Form element for this setting.
   */
  public function settingForm($config, $attribute) {
    $sitewide_access = $config->get($attribute);
    if (is_null($sitewide_access)) {
      $sitewide_access = $this->default;
    }

    $fieldArray = [
      '#type' => 'checkbox',
      '#default_value' => $sitewide_access,
      '#title' => $this->description,
      '#weight' => $this->weight,
    ];

    return $fieldArray;
  }

  /**
   * Get an array of sitewide setting form elements for this object type.
   *
   * @param $config
   *   The configuration storage.
   *
   * @return array
   *   Render array for the sitewide settings.
   */
  public static function getSitewideSettingFormElements($config) {
    return [];
  }


  /**
   * Get the markup we modify.
   *
   * @param \Drupal\Core\Field\FieldItemList $items
   *   The items to be displayed.
   * @param array $config
   *   The cached configuration used to generate the original link.
   */
  public function formatter(FieldItemList $items, $config) {
    $plugin_id = 'fasttoggle';
    $plugin_definition = $config['plugin_definition'];
    $field_definition = $config['field_definition'];
    $settings = $config['formatter_settings'];
    $label = $config['label'];
    $view_mode = $config['view_mode'];
    $third_party_settings = $config['third_party_settings'];

    $formatter_class = $this->getPluginDefinition()['base_formatter'];
    $formatter = new $formatter_class($plugin_id, $plugin_definition,
      $field_definition, $settings, $label, $view_mode,
      $third_party_settings);
    return $formatter->viewElements($items, $config['langcode']);
  }

  /**
   * Return a render array for an instance of this setting.
   */
  public function render_array($wrapper_id, $link_text, $url_args) {
    return [
      '#prefix' => "<div id='fasttoggle-{$wrapper_id}'>",
      '#suffix' => '</div>',
      '#type' => 'link',
      '#title' => $link_text,
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
      '#url' => Url::fromRoute('fasttoggle.toggle', $url_args),
    ];
  }
}
