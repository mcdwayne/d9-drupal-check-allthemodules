<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\Form\AssetForm.
 */

namespace Drupal\redhen_asset\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Asset edit forms.
 *
 * @ingroup redhen_asset
 */
class AssetForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\redhen_asset\Entity\Asset */
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
        drupal_set_message($this->t('Created %label.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved %label.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.redhen_asset.canonical', ['redhen_asset' => $entity->id()]);
  }

}
