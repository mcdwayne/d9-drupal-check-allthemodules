<?php

namespace Drupal\content_synchronizer\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for Export entity edit forms.
 *
 * @ingroup content_synchronizer
 */
class ExportEntityForm extends ContentEntityForm {

  /**
   * The export entity.
   *
   * @var \Drupal\content_synchronizer\Entity\ExportEntity
   */
  protected $export;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\content_synchronizer\Entity\ExportEntity */
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
        \Drupal::messenger()->addMessage($this->t('Created the %label Export entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Export entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.export_entity.canonical', ['export_entity' => $entity->id()]);
  }

}
