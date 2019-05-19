<?php

namespace Drupal\webform_quiz\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Webform quiz submit handler plugins.
 */
interface WebformQuizSubmitHandlerInterface extends PluginInspectionInterface {

  /**
   * Perform actions for a submit handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function handleSubmit(&$form, FormStateInterface $form_state);

}
