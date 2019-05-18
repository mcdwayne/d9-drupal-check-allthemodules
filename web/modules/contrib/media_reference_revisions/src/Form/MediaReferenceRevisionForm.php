<?php

namespace Drupal\media_reference_revisions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Media reference revision edit forms.
 *
 * @ingroup media_reference_revisions
 */
class MediaReferenceRevisionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\media_reference_revisions\Entity\MediaReferenceRevision */
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
        drupal_set_message($this->t('Created the %label Media reference revision.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Media reference revision.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.media_reference_revision.canonical', ['media_reference_revision' => $entity->id()]);
  }

}
