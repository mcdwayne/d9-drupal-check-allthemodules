<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for resetting domain entities.
 *
 * @ingroup drd
 */
class DomainReset extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset domain %name so that you have to re-authenticate this DRD instance?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\drd\Entity\DomainInterface $domain */
    $domain = $this->entity;
    return new Url('entity.drd_core.canonical', ['drd_core' => $domain->getCore()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['cryptsettings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset cryptography settings?'),
      '#default_value' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\drd\Entity\DomainInterface $domain */
    $domain = $this->entity;
    if ($form_state->getValue('cryptsettings')) {
      $domain->resetCryptSettings();
    }
    else {
      $domain->reset();
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
