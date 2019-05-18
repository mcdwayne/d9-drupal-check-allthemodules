<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\optit\Optit\Keyword;
use Drupal\optit\Optit\Optit;

/**
 * Defines a form that configures optit settings.
 */
class KeywordEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_keywords_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL) {

    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = Optit::create();

    $form = [];

    // If it is a first form edit load, we're loading keyword from the API.
    if ($keyword_id) {
      $keyword = $optit->keywordGet($keyword_id);
    }
    // If it is not an edit form, it must be a create form.
    else {
      $keyword = new Keyword();
    }

    $form['#keyword_id'] = $keyword_id;

    $this->select('billing_type', $this->t('Billing type'), $keyword, $form);

    $this->textfield('keyword_name', $this->t('Keyword name'), $keyword, $form, ['#required' => TRUE]);
    $this->textfield('internal_name', $this->t('Internal name'), $keyword, $form, ['#required' => TRUE]);

    // @todo: Add interests once you create Interest entity.

    $this->select('welcome_msg_type', $this->t('Welcome message type'), $keyword, $form);
    $this->textfield('welcome_msg', $this->t('Welcome message'), $keyword, $form, [
      '#maxlength' => 93
    ]);
    $this->select('web_form_verification_msg_type', $this->t('Web form verification message type'), $keyword, $form);
    $this->textfield('web_form_verification_msg', $this->t('Web form verification message'), $keyword, $form, [
      '#maxlength' => 120
    ]);
    $this->select('already_subscribed_msg_type', $this->t('Already subscribed message type'), $keyword, $form);
    $this->textfield('already_subscribed_msg', $this->t('Already subscribed message'), $keyword, $form, [
      '#maxlength' => 120
    ]);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit')
    ];

    return $form;
  }

  function validateForm(array &$form, FormStateInterface $form_state) {

    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = Optit::create();

    // Keyword must start with a letter.
    if (!preg_match('/^[A-Za-z]/', $form_state->getValue('keyword_name'))) {
      $form_state->setErrorByName('keyword_name', $this->t('Keyword name must start with a letter.'));
    }

    // Keyword must be longer than 4 characters.
    if (strlen($form_state->getValue('keyword_name')) <= 4) {
      $form_state->setErrorByName('keyword_name', $this->t('Keyword name must be longer than 4 characters.'));
    }

    // Keyword must contain only alphanumeric characters.
    if (!preg_match('/^[a-z0-9.]+$/i', $form_state->getValue('keyword_name'))) {
      $form_state->setErrorByName('keyword_name', $this->t('Keyword name must contain only alphanumeric characters.'));
    }

    // Keyword must be unique. No need to run the check if previous ones already failed.
    if (!$form['#keyword_id'] && !$form_state->getErrors()) {
      $keyword_exists = $optit->keywordExists($form_state->getValue('keyword_name'));
      if ($keyword_exists) {
        $form_state->setErrorByName('keyword_name', $this->t('Name must be unique. Keyword with name :name already exists.', [':name' => $form_state->getValue('keyword_name')]));
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = Optit::create();

    $keyword = Keyword::create($form_state->getValues());

    if (!$form['#keyword_id']) {
      // @todo: Add success validation.
      $optit->keywordCreate($keyword);
    }
    else {
      // @todo: Add success validation.
      $optit->keywordUpdate($form['#keyword_id'], $keyword);
    }

    if (!isset($_GET['destination'])) {
      $form_state->setRedirect('optit.structure_keywords');
    }
  }


  private function select($name, $title, $entity, &$form, $options = []) {
    $form[$name] = [
        '#type' => 'select',
        '#title' => $title,
        '#default_value' => $entity->get($name),
        '#options' => $entity->allowedValues($name),
      ] + $options;
  }

  private function textfield($name, $title, $entity, &$form, $options = []) {
    $form[$name] = [
        '#type' => 'textfield',
        '#title' => $title,
        '#default_value' => $entity->get($name),
      ] + $options;
  }

}
