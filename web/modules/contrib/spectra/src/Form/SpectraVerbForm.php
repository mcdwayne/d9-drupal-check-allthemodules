<?php

namespace Drupal\spectra\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Spectra verb edit forms.
 *
 * @ingroup spectra
 */
class SpectraVerbForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\spectra\Entity\SpectraVerb */
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
        drupal_set_message($this->t('Created the %label Spectra verb.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Spectra verb.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.spectra_verb.canonical', ['spectra_verb' => $entity->id()]);
  }

}
