<?php

namespace Drupal\entity_split\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Form controller for Entity split edit forms.
 *
 * @ingroup entity_split
 */
class EntitySplitForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\entity_split\Entity\EntitySplit */
    $form = parent::buildForm($form, $form_state);

    // Disable redirects if the form is shown in modal window.
    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    }

    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);
    $form_state->disableRedirect();

    // Disable redirects if the form is shown in modal window.
    if ($this->getRequest()->isXmlHttpRequest()) {
      $response = new AjaxResponse();
      $response->addCommand(new CloseModalDialogCommand());
      $form_state->setResponse($response);
      return;
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Entity split.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Entity split.', [
          '%label' => $entity->label(),
        ]));
    }
  }

}
