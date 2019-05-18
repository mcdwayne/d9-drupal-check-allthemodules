<?php

namespace Drupal\cludo_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class CludoSearch.
 *
 * @package Drupal\cludo_search\Form
 */
class CludoSearchBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cludo_search_block_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build form.
    $prompt = $this->t('Enter the terms you wish to search for.');
    $query = '';

    // Basic search.
    $form['search_keys'] = [
      '#type' => 'textfield',
      '#default_value' => $query,
      '#attributes' => [
        'title' => $prompt,
        'autocomplete' => 'off',
      ],
      '#title' => $prompt,
      '#title_display' => 'before',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Kill any dynamic destinations, as the results page is always the
    // destination.
    if (isset($_GET['destination'])) {
      unset($_GET['destination']);
    }

    // Set the redirect, decode to remove + and %20.
    $search_query = urldecode($form_state->getValue('search_keys'));

    // Grab any query params and pass back into redirect.
    $query = \Drupal::request()->query->all();

    // Set cludo fragment.
    $cludo_fragment = UrlHelper::buildQuery(['cludoquery' => $search_query]);

    $form_state->setRedirect(
      'cludo_search.search',
      [],
      [
        'query' => $query,
        'fragment' => '?' . $cludo_fragment,
      ]
    );

    // Search execution happens in page callback.
    $form['#action'] = '';
  }

}
