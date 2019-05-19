<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\VisualNPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for VisualN Drawer Skin plugins.
 *
 * @see \Drupal\visualn\Core\DrawerSkinBase
 *
 * @ingroup drawer_skin_plugins
 */
interface DrawerSkinInterface extends VisualNPluginInterface, PluginFormInterface {

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

}
