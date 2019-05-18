<?php

namespace Drupal\gtm_datalayer_forms\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gtm_datalayer\Plugin\DataLayerProcessorInterface;

/**
 * Defines the interface for GTM dataLayer Form Processors.
 */
interface DataLayerProcessorFormBaseInterface extends DataLayerProcessorInterface {

  /**
   * Configures dataLayer form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself.
   * @param string $form_handler
   *   The form handler: alter, validate or submit.
   *
   * @return $this
   */
  public function configure(array &$form, FormStateInterface $form_state, string $form_id, string $form_handler);

}
