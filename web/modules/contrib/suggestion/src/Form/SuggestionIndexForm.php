<?php

namespace Drupal\suggestion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\suggestion\SuggestionHelper;
use Drupal\suggestion\SuggestionStorage;

/**
 * Suggestion indexing form.
 */
class SuggestionIndexForm extends FormBase {

  /**
   * The suggestion indexing form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $synced = SuggestionHelper::getConfig('synced');

    $form['feedback'] = [
      '#markup' => '<div id="suggestion-index-feedback">' . ($synced ? $this->t('No indexing required.') : $this->t('Indexing required.')) . '</div>',
      '#weight' => 10,
    ];
    $form['flush'] = [
      '#title'         => $this->t('Flush all suggestions'),
      '#description'   => $this->t('Flushes all suggestions including priority and surfer suggestions.'),
      '#type'          => 'checkbox',
      '#default_value' => FALSE,
      '#required'      => FALSE,
      '#weight'        => 20,
    ];
    $form['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Index Suggestions'),
      '#weight' => 30,
      '#ajax'   => [
        'callback' => '\Drupal\suggestion\Form\SuggestionIndexForm::submitForm',
        'effect'   => 'fade',
        'method'   => 'replace',
        'wrapper'  => 'suggestion-index-feedback',
        'progress' => [
          'type'    => 'throbber',
          'message' => 'Please wait...',
        ],
      ],
    ];
    return $form;
  }

  /**
   * The form ID.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'suggestion_index';
  }

  /**
   * AJAX callback for the indexing form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   *
   * @return array
   *   A Drupal form array.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $t = new TranslatableMarkup('Suggestions Indexed');

    if ($form_state->getValue('flush')) {
      SuggestionStorage::truncateSuggestion();
    }
    SuggestionHelper::index();

    return ['#markup' => '<div id="suggestion-index-feedback"><p>' . $t->render() . '</p></div>'];
  }

}
