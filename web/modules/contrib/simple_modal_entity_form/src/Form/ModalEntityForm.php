<?php

namespace Drupal\simple_modal_entity_form\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_modal_entity_form\Ajax\ModalEntityFormScrollTopCommand;
use Drupal\views\Ajax\ScrollTopCommand;

/**
 * Generic Handler for Modal forms.
 */
class ModalEntityForm extends ContentEntityForm {

  function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('modal_form_display');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    $element['submit']['#ajax'] = [
      'callback' => '::ajaxFormSubmitHandler',
      'wrapper' => 'modal-form',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Saving...'),
      ],
    ];
    return $element;
  }

  /**
   * Validate
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxFormSubmitHandler(array &$form, FormStateInterface $form_state){
    $response = new AjaxResponse();
    if (!empty($form_state->getErrors())) {
      $response->addCommand(new ModalEntityFormScrollTopCommand());
      $response->addCommand(new ReplaceCommand('#modal-form', $form));
      return $response;
    }
    $this->submitForm($form, $form_state);
    \Drupal::messenger()->addMessage(t('Succesfully saved.'));
    $response->addCommand(new RedirectCommand(\Drupal::request()->query->get('destination')));
    return $response;
  }

}
