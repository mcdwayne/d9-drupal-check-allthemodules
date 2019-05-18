<?php

namespace Drupal\empty_page\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\empty_page\Form\CallbackForm;
use Drupal\Core\Url;

/**
 * Provides a form to delete a empty page.
 */
class CallbackDeleteForm extends ConfirmFormBase {

  private $callback;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'empty_page_callback_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the callback for <em>:path</em>?', [':path' => $this->callback['path']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete callback');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('empty_page.administration');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = NULL) {
    $settings = \Drupal::configFactory()->get('empty_page.settings');
    $callback = $settings->get('callback_' . $cid);
    $this->callback = $callback;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $callback = $this->callback;
    \Drupal::configFactory()->getEditable('empty_page.settings')->clear('callback_' . $callback['cid'])->save();
    \Drupal::service('router.builder')->rebuild();
    drupal_set_message($this->t('Callback <em>:path</em> deleted.', [':path' => $callback->path]));
    $form_state->setRedirect('empty_page.administration');
  }

}
