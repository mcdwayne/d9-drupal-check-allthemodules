<?php

namespace Drupal\ipfs_backend\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for IPFSHash edit forms.
 *
 * @ingroup ipfs_backend
 */
class IPFSHashForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ipfs_backend\Entity\IPFSHash */
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
        drupal_set_message($this->t('Created the %label IPFSHash.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label IPFSHash.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.ipfs_hash.canonical', ['ipfs_hash' => $entity->id()]);
  }

}
