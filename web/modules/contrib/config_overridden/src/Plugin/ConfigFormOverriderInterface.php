<?php

namespace Drupal\config_overridden\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * COnfiguration form override interface.
 */
interface ConfigFormOverriderInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Highlights overrides.
   */
  public function highlightOverrides();

  /**
   * To check to isApplicable or not.
   *
   * @return bool
   *   Return boolean value from the conditions.
   */
  public function isApplicable();

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param null $form_id
   */
  public function setForm(array &$form, FormStateInterface $form_state, $form_id = NULL);
}
