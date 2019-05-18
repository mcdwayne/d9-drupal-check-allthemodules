<?php

namespace Drupal\entity_generic\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for entity forms.
 */
class GenericModalForm extends GenericForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // Add path and query parameters from request to use them later in submitting flow.
    $request = \Drupal::requestStack()->getCurrentRequest();
    $referer = $request->headers->get('referer');
    $referer_parsed = parse_url($referer);
    $query_array = [];
    if (isset($referer_parsed['query']) && $referer_parsed['query']) {
      parse_str($referer_parsed['query'], $query_array);
    }
    $form_state->addBuildInfo('referer_query_array', $query_array);
    $form_state->addBuildInfo('referer_path', $referer_parsed['path']);

    // Setup the form.
    $form = parent::form($form, $form_state);
    // Disable cache for modal form.
    $form_state->setCached(FALSE);

    $form['#tree'] = TRUE;

    // Add a special wrapper to use it in submitting flow.
    $form['#prefix'] = '<div id="entity_generic_modal_form_wrapper">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    // Attach necessary libs.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Form actions.
    $actions['submit']['#ajax'] = [
      'callback' => [$this, 'submitModalAjax'],
      'event' => 'click',
    ];
    $actions['submit']['#attributes'] = [
      'class' => [
        'use-ajax',
      ]
    ];

    return $actions;
  }

  /**
   * AJAX callback handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function submitModalAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      // Show error messages.
      $response->addCommand(new ReplaceCommand('#entity_generic_modal_form_wrapper', $form));

      // Process actions for the submit fail case.
      $this->submitModalAjaxFail($response, $form, $form_state);
    }
    else {
      // Close modal dialog.
      $response->addCommand(new CloseModalDialogCommand());

      // Process actions for the successful submit case.
      $this->submitModalAjaxSuccess($response, $form, $form_state);
    }

    return $response;
  }

  /**
   * AJAX callback handler failed submission.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitModalAjaxFail(AjaxResponse &$response, array $form, FormStateInterface $form_state) {

  }

  /**
   * AJAX callback handler successful submission.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitModalAjaxSuccess(AjaxResponse &$response, array $form, FormStateInterface $form_state) {

  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();
  }

}
