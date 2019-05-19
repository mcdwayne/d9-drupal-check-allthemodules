<?php

/**
 * @file
 * Conatins SetBasicProperty class.
 */

namespace Drupal\visualn_subdrawers\Plugin\VisualN\DrawerModifier;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierBase;
use Drupal\visualn\ConfigurableDrawerModifierBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a 'Set Basic Property' VisualN drawer modifier.
 *
 * @VisualNDrawerModifier(
 *  id = "visualn_set_basic_property",
 *  label = @Translation("Set Basic Property"),
 * )
 */
class SetBasicProperty extends ConfigurableDrawerModifierBase {

  /**
   * {@inheritdoc}
   */
  public function methodsSubstitutionsInfo() {
    // @todo: basically if nothing set, modifyBuildConfigurationForm shouldn't be listed

    // may containt 'before' and 'after' info
    $substitutions = [
      // modify form, add elements
      'buildConfigurationForm' => ['after' => 'modifyBuildConfigurationForm'],
      // modify default values, set default values for new elements
      'defaultConfiguration' => ['after' => 'modifyDefaultConfiguration'],
      // modify configuration values, set user values for new elements
      'getConfiguration' => ['after' => 'modifyGetConfiguration'],
      // modify configuration values for the js script
      'prepareJsConfig' => ['after' => 'modifyPrepareJsConfig'],
    ];
    return $substitutions;
  }

  public function modifyGetConfiguration($configuration) {
    // @todo: is this method needed here since it is also may be used internally in drawers?

    return $configuration;
  }

  public function modifyPrepareJSCofig(array &$drawer_config, $original_drawer_config) {
    // @todo: this place can generally be used to override selected setup
    // @todo:
    $property_path = trim($this->configuration['property_path']);
    // @todo: trim every $array_parents element and remove empty elements

    // @todo: a more generic way to explode at new lines?
    //$array_parents = explode("\n", $property_path);
    $array_parents = preg_split('/\r\n|[\r\n]/', $property_path);
    // @todo: check $array_parents for elements here
    if (empty($property_path)) {
      return;
    }
    $element_title = trim($this->configuration['element_title']);

    $element_key = trim($this->configuration['element_key']);
    if (empty($element_key)) {
      return;
    }

    //$value_array_parents = explode("\n", $element_key);
    $value_array_parents = preg_split('/\r\n|[\r\n]/', $element_key);
    if (NestedArray::keyExists($original_drawer_config, $value_array_parents)) {
      $value = NestedArray::getValue($original_drawer_config, $value_array_parents);
    }
    NestedArray::setValue($drawer_config, $array_parents, $value);
  }

  public function modifyDefaultConfiguration($originial_default_values, $drawer_config) {
    // @todo: we can't override defaultConfiguration() directly because it is used internally in the drawer.
    //    in most cases it is used in, it is ok just to set drawer configuration to override default config values
    $default_values = $originial_default_values;

    $element_key = trim($this->configuration['element_key']);
    $element_default_value = trim($this->configuration['element_default_value']);
    $default_values[$element_key] = $element_default_value;

    return $default_values;
  }

  // @todo: pass also drawer instance object into arguments (or drawer config which is better)
  public function modifyBuildConfigurationForm(array $form, FormStateInterface $form_state, $drawer_config) {
    // @todo: check if trims also new lines
    $element_key = trim($this->configuration['element_key']);
    if (empty($element_key)) {
      return $form;
    }
    // @todo: do additional checks
    $element = [
      '#type' => $this->configuration['element_type'],
      '#title' => t($this->configuration['element_title']),
      '#default_value' => $drawer_config[$element_key],
      '#required' => $this->configuration['element_required'],
    ];
    if (!isset($form[$element_key])) {
      $form[$element_key] = $element;
    }

    return $form;
  }

  public function defaultConfiguration() {
    return [
      'element_key' => '',
      'element_title' => '',
      'element_required' => '',
      'element_type' => '',
      'element_default_value' => '',
      'property_path' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
  // @todo: check user input to avoid code injection

    // @todo: add #element_validate for the element_key
    $form['element_key'] = [
      '#type' => 'textfield',
      '#title' => t('Element key for the form element'),
      '#default_value' => $this->configuration['element_key'],
      '#required' => TRUE,
    ];
    $form['element_title'] = [
      '#type' => 'textfield',
      '#title' => t('Element title'),
      '#default_value' => $this->configuration['element_title'],
      '#required' => TRUE,
    ];
    $form['element_required'] = [
      '#type' => 'checkbox',
      '#title' => t('Element is required'),
      '#default_value' => $this->configuration['element_required'],
    ];

    $element_types = [
      '' => t('- Select -'),
      'textfield' => t('Textfield'),
      'textarea' => t('Textarea'),
      'select' => t('Select list'),
    ];
    $form['element_type'] = [
      '#type' => 'select',
      '#title' => t('Element type'),
      '#default_value' => $this->configuration['element_type'],
      '#options' => $element_types,
      '#required' => TRUE,
    ];
    $form['element_default_value'] = [
      '#type' => 'textfield',
      '#title' => t('Element default value'),
      '#default_value' => $this->configuration['element_default_value'],
      '#required' => TRUE,
    ];
    $form['property_path'] = [
      '#type' => 'textarea',
      '#title' => t('Propery path to set value for'),
      '#default_value' => $this->configuration['property_path'],
      '#required' => TRUE,
    ];

    // @todo: it is safe here to use #ajax settings specific for certain element type
    //    e.g. textfield or textarea for default value for the element

    // @todo: a select may be added for added element validate function (not the ones from here)
    //    method::function or regex

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // @todo: extract all values from getValues(), move into VisualNDrawerModifierBase class
    $this->configuration['element_key'] = $form_state->getValue('element_key');
    $this->configuration['element_title'] = $form_state->getValue('element_title');
    $this->configuration['element_required'] = $form_state->getValue('element_required');
    $this->configuration['element_type'] = $form_state->getValue('element_type');
    $this->configuration['element_default_value'] = $form_state->getValue('element_default_value');
    $this->configuration['property_path'] = $form_state->getValue('property_path');
  }

}
