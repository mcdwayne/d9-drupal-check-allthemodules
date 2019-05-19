<?php

/**
 * @file
 * Conatins DrawerWSSBoilerplate class
 */

namespace Drupal\visualn\DrawerBoilerplate;

use Drupal\visualn\Core\DrawerWithJsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Boilerplate class for VisualN Drawer plugins.
 *
 * @see \Drupal\visualn\Core\DrawerWithJsBase
 * @see \Drupal\visualn\Core\DrawerWithJsInterface
 */
abstract class DrawerWSSBoilerplate extends DrawerWithJsBase {
  // @todo: this will always provide a DrawerWithJs drawer even when js not used

  /**
   * @inheritdoc
   */
/*
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'module_name/drawer-library';

    return $resource;
  }
*/

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'drawer_setup_id' => '',
      //'data_keys' => '',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    // The id of the VisualNSetup config entity
    $form['drawer_setup_id'] = [
      '#type' => 'select',
      '#title' => t('Drawer Setup'),
      '#options' => visualn_setup_options(FALSE),
      '#default_value' => $configuration['drawer_setup_id'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
/*
  public function jsId() {
    return 'module_nameClassName';
  }
*/

  /**
   * @inheritdoc
   */
  public function prepareJsConfig(array &$drawer_config) {

    // @todo: this can be a added to the DrawerBase class to be used across all WSS drawers (or to a trait)
    $visualn_setup_id = $drawer_config['drawer_setup_id'];
    // load setup entity
    $visualn_setup = \Drupal::service('entity_type.manager')->getStorage('visualn_setup')->load($visualn_setup_id);
    $setup_baker = $visualn_setup->getSetupBakerPlugin();

    // get setup from drawer setup entity
    // we expect the setup to be already json_decoded (actually an array)
    // as in $drawer_config['drawer_setup'] = json_decode($drawer_config['drawer_setup'], TRUE);
    $drawer_setup = $setup_baker->bakeSetup();


    // set drawer_setup key to send settings to js (the key is used in the base class)
    $drawer_config['drawer_setup'] = $drawer_setup;
  }

  /**
   * @inheritdoc
   */
/*
  public function dataKeys() {
    $data_keys_str = trim($this->configuration['data_keys']);
    if (!empty($data_keys_str)) {
      $data_keys = explode(',', $data_keys_str);
      // @todo: trim every key string
    }
    else {
      $data_keys = [];
    }

    return $data_keys;
  }
*/

}
