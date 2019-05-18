<?php

namespace Drupal\regex_redirect\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class RegexRedirectDeleteForm.
 *
 * @package Drupal\regex_redirect\Form
 */
class RegexRedirectDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect $redirect */
    $redirect = $this->entity;
    return $this->t('Are you sure you want to delete the regex redirect from %source to %redirect?', ['%source' => $redirect->getSourceUrl(), '%redirect' => $redirect->getRedirectUrl()->toString()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('regex_redirect.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Regex redirect form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect $redirect */
    $redirect = $this->entity;
    $redirect->delete();
    $this->messenger->addMessage(t('The regex redirect %redirect has been deleted.', ['%redirect' => $redirect->getRedirectUrl()->toString()]));
    $form_state->setRedirect('regex_redirect.list');
  }

}
