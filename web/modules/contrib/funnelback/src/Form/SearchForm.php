<?php

namespace Drupal\funnelback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Funnelback search form.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'funnelback_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState) {
    // Find out current search query.
    $query_string = drupal_get_query_parameters();
    $query = '';

    if (isset($query_string['query'])) {
      $query = check_plain(filter_xss($query_string['query']));
      $query = str_replace("`", '', $query);
    }

    $form['funnelback_search_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => $query,
      '#attributes' => ['title' => $this->t('Enter the terms you wish to search for.')],
    ];

    // Add auto-completion.
    $auto_completion = funnelback_get_config()->get('autocomplete.enabled', FALSE);
    if ($auto_completion) {
      $form['funnelback_search_field']['#autocomplete_path'] = 'funnelback/search/autocompletion';
    }

    $form['funnelback_search_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $query = check_plain(filter_xss($formState['values']['funnelback_search_field']));

    $url = Url::fromRoute('funnelback.search');
    $url->setOption('query', $query);

    $formState->setRedirectUrl($url);
  }

}
