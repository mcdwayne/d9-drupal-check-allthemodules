<?php

namespace Drupal\cleantalk\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;
use Drupal\cleantalk\CleantalkHelper;

class CleantalkCheckCommentsForm extends FormBase {

  /**
   * {@inheritdoc}
   */

  public function getFormId() {

    return 'cleantalk_check_comments_form';

  }

  /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return ['cleantalk.check_comments'];

  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::messenger()->addMessage(t("Will be available soon!"), 'warning', false);
    return $form;

  }

}
