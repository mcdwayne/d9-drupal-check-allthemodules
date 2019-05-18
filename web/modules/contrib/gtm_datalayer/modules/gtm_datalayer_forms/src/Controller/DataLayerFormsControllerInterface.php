<?php

namespace Drupal\gtm_datalayer_forms\Controller;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gtm_datalayer\Controller\DataLayerControllerInterface;

/**
 * Defines the interface for GTM dataLayer Controller.
 */
interface DataLayerFormsControllerInterface extends DataLayerControllerInterface {

  /**
   * Builds Google Tag Manager dataLayer pusher (script).
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself.
   * @param string $form_handler
   *   The form handler: alter, validate or submit.
   */
  public function buildGtmPusherScript(array &$form, FormStateInterface $form_state, string $form_id, string $form_handler = 'alter');

  /**
   * Push Google Tag Manager dataLayer stores in the current session.
   *
   * @param array &$attachments
   *   An array that you can add attachments to.
   */
  public function pushGtmTags(&$attachments);

}
