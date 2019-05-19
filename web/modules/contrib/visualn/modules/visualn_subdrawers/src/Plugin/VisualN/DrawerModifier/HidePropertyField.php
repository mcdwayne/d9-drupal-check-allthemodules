<?php

/**
 * @file
 * Conatins HidePropertyField class.
 */

namespace Drupal\visualn_subdrawers\Plugin\VisualN\DrawerModifier;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierBase;
use Drupal\visualn\ConfigurableDrawerModifierBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a 'Hide Property Field' VisualN drawer modifier.
 *
 * @VisualNDrawerModifier(
 *  id = "visualn_hide_property_field",
 *  label = @Translation("Hide Property Field"),
 * )
 */
class HidePropertyField extends ConfigurableDrawerModifierBase {

  /**
   * {@inheritdoc}
   */
  public function methodsSubstitutionsInfo() {
    // @todo: basically if no element to hide set in modifier config, modifyBuildConfigurationForm shouldn't be listed

    // may containt 'before' and 'after' info
    $substitutions = [
      'buildConfigurationForm' => ['after' => 'modifyBuildConfigurationForm'],
    ];
    return $substitutions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'element_path' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: add #element_validate
    $form['element_path'] = [
      '#type' => 'textarea',
      '#title' => t('Element path'),
      '#default_value' => $this->configuration['element_path'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // @todo: extract all values from getValues(), move into VisualNDrawerModifierBase class
    $this->configuration['element_path'] = $form_state->getValue('element_path');
  }

  public function modifyBuildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: check if trims also new lines
    $element_path = trim($this->configuration['element_path']);
    if (empty($element_path)) {
      return $form;
    }
    //$array_parents = explode("\n", $element_path);
    $array_parents = preg_split('/\r\n|[\r\n]/', $element_path);
    // @todo: trim every $array_parents element and remove empty elements
    if (NestedArray::keyExists($form, $array_parents)) {
      // @todo: if empty, continue
      array_push($array_parents, '#type');
      NestedArray::setValue($form, $array_parents, 'value');
    }

    return $form;
  }

}
