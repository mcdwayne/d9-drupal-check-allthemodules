<?php

namespace Drupal\entity_log\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Entity log edit forms.
 *
 * @ingroup entity_log
 */
class EntityLogForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\entity_log\Entity\EntityLog */
    $form = parent::buildForm($form, $form_state);
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
        $this->messenger()->addStatus($this->t('Created the %label Entity log.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Entity log.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.entity_log.canonical', ['entity_log' => $entity->id()]);
  }

}
