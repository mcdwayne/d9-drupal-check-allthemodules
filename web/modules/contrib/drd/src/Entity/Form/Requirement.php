<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Requirement edit forms.
 *
 * @ingroup drd
 */
class Requirement extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\drd\Entity\RequirementInterface $requirement */
    $form = parent::buildForm($form, $form_state);
    $requirement = $this->entity;

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $requirement->getLangCode(),
      '#languages' => Language::STATE_ALL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $requirement = $this->entity;
    $status = $requirement->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Requirement.', [
          '%label' => $requirement->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Requirement.', [
          '%label' => $requirement->label(),
        ]));
    }
    $form_state->setRedirect('entity.drd_requirement.canonical', ['drd_requirement' => $requirement->id()]);
  }

}
