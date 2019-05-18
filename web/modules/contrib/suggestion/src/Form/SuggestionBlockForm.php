<?php

namespace Drupal\suggestion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\suggestion\SuggestionHelper;

/**
 * Builds the search form for the search block.
 */
class SuggestionBlockForm extends FormBase {

  /**
   * A search block form.
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
    $cfg = SuggestionHelper::getConfig();

    $form['#action'] = $cfg->action;
    $form['#form_id'] = 'suggestion_block_form';
    $form['#method'] = 'get';

    $form['keys'] = [
      '#type'                    => 'search',
      '#title'                   => $this->t('Search'),
      '#title_display'           => 'invisible',
      '#size'                    => $cfg->max,
      '#default_value'           => '',
      '#attributes'              => ['title' => $this->t('Enter the terms you wish to search for.')],
      '#autocomplete_route_name' => 'suggestion.autocomplete',
    ];
    $form['actions'] = [
      '#type'  => 'actions',
      'submit' => [
        '#type'       => 'submit',
        '#name'       => '',
        '#attributes' => ['class' => ['search-form__submit']],
        '#value'      => $this->t('Search'),
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
    return 'suggestion_block_form';
  }

  /**
   * Dumy submit function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
