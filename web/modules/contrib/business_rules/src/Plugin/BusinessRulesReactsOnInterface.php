<?php

namespace Drupal\business_rules\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Business rules reacts on plugins.
 */
interface BusinessRulesReactsOnInterface extends PluginInspectionInterface {

  /**
   * Process the BusinessRule form for reactsOn plugins.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function processForm(array &$form, FormStateInterface $form_state);

}
