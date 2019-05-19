<?php

namespace Drupal\visualn\Core;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Core\VisualNPluginInterface;
use Drupal\visualn\WindowParametersInterface;

/**
 * Defines an interface for VisualN Drawer plugins.
 *
 * @see \Drupal\visualn\Core\DrawerBase
 *
 * @ingroup drawer_plugins
 */
interface DrawerInterface extends VisualNPluginInterface, PluginFormInterface, WindowParametersInterface {

  /**
   * Get drawer description text
   *
   * Drawer may use current configuration to provide actual description.
   */
  public function getDescription();

  /**
   * Extract drawer configuration array values from $form_state for drawer configuration form.
   *
   * @param array $form
   *
   * @param  \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array $values
   *   An array of drawer_config values.
   */
  public function extractFormValues($form, FormStateInterface $form_state);

  /**
   * Return a list of data keys used by the drawer script.
   *
   * @todo: think of returning a more complex data map then just and array of keys
   *
   * @return array $data_keys
   */
  public function dataKeys();

  /**
   * Return data keys used by mapper script.
   *
   * @return array $data_keys
   */
  public function dataKeysStructure();

  // @todo: add external url into description

  //public function getDescription();
  //public function getDescriptionExternalUrl();

}
