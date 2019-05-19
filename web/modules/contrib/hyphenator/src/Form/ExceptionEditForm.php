<?php

namespace Drupal\hyphenator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exceptions edit form.
 */
class ExceptionEditForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'exception_edit_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['hyphenator.add_exception'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @param \Drupal\node\Entity\Node|null $platform
   *
   * @return array The form structure.
   * The form structure.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $language = NULL) {
    if (is_null($language)) {
      $language = 'GLOBAL';
      $words = '';
      $new = TRUE;
    }
    else {
      $exceptions = \Drupal::state()->get('hyphenator_exceptions', []);
      $words = (array_key_exists($language, $exceptions)) ? $exceptions[$language] : '';
      $new = FALSE;
    }

    $languages = _hyphenator_get_available_languages();
    $form = [];

    if (!empty($languages)) {
      $languages = array_merge(['GLOBAL' => 'GLOBAL'], $languages);
    }

    $form['message'] = [
      '#markup' => ($new) ? t('Add one or more exception words for a specified language.') : t('Modify the exception words list for the specified language.'),
    ];

    $form['language'] = [
      '#title' => t('Language'),
      '#disabled' => !$new,
      '#description' => t("Use 'GLOBAL' :blato enable the associated words to be skipped for any languages.", [':bla' => empty($languages) ? t('or an empty string') . ' ' : '']),
      '#default_value' => $language,
    ];

    if (!empty($languages)) {
      $languages = array_merge(['GLOBAL' => 'GLOBAL'], $languages);
      $form['language']['#type'] = 'select';
      $form['language']['#options'] = $languages;
    }
    else {
      $form['language']['#type'] = 'textfield';
      $form['language']['#size'] = 10;
      $form['language']['#maxlength'] = 15;
    }

    $form['exception_words'] = [
      '#type' => 'textarea',
      '#title' => t('Exception words'),
      '#description' => t('Begin a newline to add a word.'),
      '#default_value' => $words,
    ];

    $form['exception_new'] = [
      '#type' => 'hidden',
      '#value' => $new,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save exception'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#href' => 'admin/config/content/hyphenator',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Exceptions edit form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $language = $form_state->getValue('language');
    $new = $form_state->getValue('exception_new');
    $words = $form_state->getValue('exception_words');
    $exceptions = \Drupal::state()->get('hyphenator_exceptions', []);

    if (empty($language)) {
      $language = 'GLOBAL';
    }

    if (array_key_exists($language, $exceptions) && $new) {
      $form_state->setErrorByName('language', t('The Language %lang that you submitted already exists.', ['%lang' => $language]));
    }

    if (!preg_match('/^(\s*\S+\s*)+$/', $words)) {
      $form_state->setErrorByName('exception_words', t("The 'Exception words' field must be set with some strings."));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Exceptions edit form submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('hyphenator.settings');
    $language = $form_state->getValue('language');
    $words = $form_state->getValue('exception_words');

    if (empty($language)) {
      $language = 'GLOBAL';
    }

    $exceptions = \Drupal::state()->get('hyphenator_exceptions', []);
    $exceptions[$language] = trim($words);
    \Drupal::state()->set('hyphenator_exceptions', $exceptions);
    \Drupal::messenger()->addMessage(t("The hyphenator exception language '%lang' have been saved.", ['%lang' => $language]));

    parent::submitForm($form, $form_state);
  }
}
