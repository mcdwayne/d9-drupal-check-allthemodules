<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\optit\Optit\Optit;

/**
 * Defines a form that creates an interest.
 * The form is ill-named as the API does not support editing of interests!
 */
class InterestEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_interests_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL) {

    $form = [];

    $form['keywordId'] = [
      "#type" => 'value',
      "#value" => $keyword_id
    ];

    $form['name'] = [
      '#title' => t('Name'),
      '#description' => t('Name of the interest'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#description' => t('Description of the interest'),
      '#type' => 'textfield',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit')
    ];

    return $form;
  }

  function validateForm(array &$form, FormStateInterface $form_state) {

    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = Optit::create();

    // Make sure there are not interests with the same name attached to the given keyword.
    $interests = $optit->interestsGet($form_state->getValue('keywordId'), $form_state->getValue('name'));
    if (count($interests) > 0) {
      $form_state->setErrorByName('name', t('There is already an interest with the given name. Please choose different name.'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Initiate bridge class and dependencies and get the list of keywords from the API.
    $optit = Optit::create();

    if ($optit->interestCreate($form_state->getValue('keywordId'), $form_state->getValue('name'), $form_state->getValue('description'))) {
      drupal_set_message($this->t('Interest successfully saved.'));
    }
    else {
      drupal_set_message($this->t('Interest could not be saved.', 'error'));
    }

    if (!isset($_GET['destination'])) {
      $form_state->setRedirect('optit.structure_keywords_interests', [
        'keyword_id' => $form_state->getValue('keywordId')
      ]);
    }
  }
}
