<?php

namespace Drupal\search_365\Form;

use Drupal\search_365\Routing\SearchViewRoute;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the search form.
 *
 * @package Drupal\search_365\Form
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_365_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $query = '') {
    $prompt = $this->t('Please enter search terms.');

    // Basic search.
    $form['basic'] = [
      '#type' => 'container',
    ];
    $form['basic']['search_keys'] = [
      '#type' => 'textfield',
      '#default_value' => $query,
      '#attributes' => [
        'title' => $prompt,
        'placeholder' => $prompt,
      ],
      '#title' => $prompt,
      '#title_display' => 'invisible',
    ];

    // @todo: sort.

    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    // Use core search CSS in addition to this module's css
    // (keep it general in case core search is enabled).
    $form['#attributes']['class'][] = 'search-form';
    $form['#attributes']['class'][] = 'search-365-search-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $searchQuery = urlencode($form_state->getValue('search_keys'));

    $form_state->setRedirect(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $searchQuery,
    ]);
  }

}
