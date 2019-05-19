<?php

namespace Drupal\spectra\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Spectra noun edit forms.
 *
 * @ingroup spectra
 */
class SpectraNounForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\spectra\Entity\SpectraNoun */
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
        drupal_set_message($this->t('Created the %label Spectra noun.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Spectra noun.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.spectra_noun.canonical', ['spectra_noun' => $entity->id()]);
  }

}
