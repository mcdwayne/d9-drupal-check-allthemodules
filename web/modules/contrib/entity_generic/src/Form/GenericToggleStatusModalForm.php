<?php

namespace Drupal\entity_generic\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to toggle the status of the entity.
 */
class GenericToggleStatusModalForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#prefix'] = '<div id="entity_generic_toggle_status_modal_form_wrapper">';
    $form['#suffix'] = '</div>';

    // Override description.
    $form['description'] = ['#markup' => $this->getQuestion()];

    // Override actions.
    $form['actions']['submit']['#attributes'] = [
      'class' => [
        'use-ajax',
      ]
    ];
    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'submitModalFormAjax'],
      'event' => 'click',
    ];

    unset($form['actions']['cancel']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_generic_toggle_status_modal_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $action = $this->entity->get('status') && $this->entity->get('status')->value ? 'disable' : 'enable';
    return $this->t('Are you sure you want to ' . $action . ' %name?', ['%name' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $action = $this->entity->get('status') && $this->entity->get('status')->value ? 'Disable' : 'Enable';
    return $this->t($action);
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      // Show error messages.
      $response->addCommand(new ReplaceCommand('#entity_generic_toggle_status_modal_form_wrapper', $form));

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->entity->get('status')) {
      if (!$this->entity->get('status')->value) {
        $this->entity->set('status', TRUE);
      }
      else {
        $this->entity->set('status', FALSE);
      }
      $this->entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return NULL;
  }

}
