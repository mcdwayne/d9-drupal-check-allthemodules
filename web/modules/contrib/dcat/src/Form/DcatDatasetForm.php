<?php

namespace Drupal\dcat\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Dataset edit forms.
 *
 * @ingroup dcat
 */
class DcatDatasetForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\dcat\Entity\DcatDataset */
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
        drupal_set_message($this->t('Created the %label Dataset.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Dataset.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.dcat_dataset.canonical', ['dcat_dataset' => $entity->id()]);
  }

}
