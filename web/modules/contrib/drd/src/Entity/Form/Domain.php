<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Domain edit forms.
 *
 * @ingroup drd
 */
class Domain extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\drd\Entity\DomainInterface $requirement */
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
    $entity = $this->entity;
    $entity->save();
    drupal_set_message($this->t('Saved the %label Domain.', [
      '%label' => $entity->label(),
    ]));
    $form_state->setRedirect('entity.drd_domain.canonical', ['drd_domain' => $entity->id()]);
  }

}
