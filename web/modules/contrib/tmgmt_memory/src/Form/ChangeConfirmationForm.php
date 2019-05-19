<?php

namespace Drupal\tmgmt_memory\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Views;

/**
 * Change state confirmation form.
 */
class ChangeConfirmationForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return new TranslatableMarkup('Are you sure you want to change the state of this translation?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $view = Views::getView('tmgmt_memory');
    $view->initDisplay();
    return $view->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt_memory\SegmentTranslationInterface $entity */
    $entity = $this->getEntity();

    $old_state = $entity->getState();
    $entity->setState(!$old_state);
    $entity->save();

    drupal_set_message(t('State changed.'));

    $view = Views::getView('tmgmt_memory');
    $view->initDisplay();
    $form_state->setRedirect($view->getUrl()->getRouteName());
  }

}
