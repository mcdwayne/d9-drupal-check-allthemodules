<?php

namespace Drupal\hyphenator\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exceptions delete form.
 */
class ExceptionDeleteForm extends ConfirmFormBase {

  /**
   * The language.
   *
   * @var string
   */
  protected $language;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'exception_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $language = NULL) {
    if (!$this->language = $language) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Exceptions delete form submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('hyphenator.settings');
    $language = $this->language;
    $exceptions = \Drupal::state()->get('hyphenator_exceptions', []);

    if (empty($language)) {
      $language = 'GLOBAL';
    }

    unset($exceptions[$language]);
    \Drupal::state()->set('hyphenator_exceptions', $exceptions);
    \Drupal::messenger()->addMessage(t("The hyphenator language %lang and its exception(s) have been deleted.", ['%lang' => $language]));
  }

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return t('Are you sure you want to delete de Hyphenator language %l and its exception(s)?', array('%l' => $this->language));
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return Url::fromRoute('hyphenator.settings');
  }
}
