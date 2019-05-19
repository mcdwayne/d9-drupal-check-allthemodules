<?php

namespace Drupal\visualn_iframe\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for VisualN IFrame edit forms.
 *
 * @ingroup iframes_toolkit
 */
class VisualNIFrameForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\visualn_iframe\Entity\VisualNIFrame */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN IFrame.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN IFrame.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.visualn_iframe.canonical', ['visualn_iframe' => $entity->id()]);
  }

}
