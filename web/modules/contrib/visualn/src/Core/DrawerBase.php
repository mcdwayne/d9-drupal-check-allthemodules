<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Utility\NestedArray;
use Drupal\visualn\Entity\VisualNStyleInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Core\DrawerInterface;
use Drupal\visualn\Core\VisualNPluginBase;
use Drupal\visualn\ResourceInterface;
use Drupal\visualn\WindowParametersTrait;

/**
 * Base class for VisualN Drawer plugins.
 *
 * @see \Drupal\visualn\Core\DrawerInterface
 *
 * @ingroup drawer_plugins
 */
abstract class DrawerBase extends VisualNPluginBase implements DrawerInterface {

  use WindowParametersTrait;

  /**
   * {@inheritdoc}
   */
  public function setWindowParameters(array $window_parameters) {
    $this->window_parameters = $window_parameters;
    $this->cleanWindowParameters();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Clean submitted values
    $drawer_config = $this->extractFormValues($form, $form_state);
    $form_state->setValues($drawer_config);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //The drawer_config from form_state should not change the plugin configuration,
    //it is only used to build the form according to that config.
    $drawer_config = $this->extractFormValues($form, $form_state);

    $form['markup'] = [
      '#markup' => '<div>' . t('No configuration provided for this drawer') . '</div>',
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    // Since it is supposed to be subform_state, get all the values without limiting the scope.
    return $form_state->getValues();
  }

  // @todo: remove legacy methods

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    return [];
  }

  /**
   * @inheritdoc
   */
  public function dataKeysStructure() {
    return [];
  }

  // @todo: should it be d3.js or just a generic js object?
  //'input' =>$this->pluginDefinition['input'], // this input type represents a generic d3.js object with correctly mapped data keys

}
