<?php

namespace Drupal\chatbot\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Workflow edit forms.
 *
 * @ingroup chatbot
 */
class WorkflowForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\chatbot\Entity\Workflow */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Workflow.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Workflow.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.chatbot_workflow.canonical', ['chatbot_workflow' => $entity->id()]);
  }

}
