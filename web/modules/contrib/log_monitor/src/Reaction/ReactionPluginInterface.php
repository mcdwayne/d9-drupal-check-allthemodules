<?php

namespace Drupal\log_monitor\Reaction;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Reaction plugin plugins.
 */
interface ReactionPluginInterface extends PluginInspectionInterface {


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * The actual action to be performed by the reaction.
   *
   * @param $args
   */
  public function action($args);

}
