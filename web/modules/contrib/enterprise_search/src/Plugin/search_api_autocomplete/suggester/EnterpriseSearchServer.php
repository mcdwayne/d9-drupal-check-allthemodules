<?php

namespace Drupal\enterprise_search\Plugin\search_api_autocomplete\suggester;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_autocomplete\AutocompleteBackendInterface;
use Drupal\search_api_autocomplete\Suggester\SuggesterInterface;
use Drupal\search_api_autocomplete\Suggester\SuggesterPluginBase;

/**
 * Provides a suggester plugin that retrieves suggestions from the server.
 *
 * The server needs to support the "search_api_autocomplete" feature for this to
 * work.
 *
 * @SearchApiAutocompleteSuggester(
 *   id = "enterprise_search_server",
 *   label = @Translation("Retrieve from Enterprise Search server"),
 *   description = @Translation("Make suggestions based on the data indexed on the Enterprise Search server."),
 * )
 */
class EnterpriseSearchServer extends SuggesterPluginBase implements SuggesterInterface {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    return (bool) static::getBackend($index);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Add a list of fields to include for autocomplete searches.
    $search = $this->getSearch();
    $fields = $search->getIndexInstance()->getFields();
    $fulltext_fields = $search->getIndexInstance()->getFulltextFields();

    $options = [];
    foreach ($fulltext_fields as $field) {
      if ($fields[$field]->getDataTypePlugin()->getPluginId() === 'solr_text_ngram')
        $options[$field] = $fields[$field]->getFieldIdentifier();
    }
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select used fields'),
      '#description' => $this->t('Select the fields which should be searched for matches when looking for autocompletion suggestions.'),
      '#options' => $options,
      '#default_value' => array_combine($this->getConfiguration()['fields'], $this->getConfiguration()['fields']),
      '#attributes' => ['class' => ['search-api-checkboxes-list']],
    ];
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values['fields'] = array_keys(array_filter($values['fields']));
    $this->setConfiguration($values);
  }


  /**
   * {@inheritdoc}
   */
  public function getAutocompleteSuggestions(QueryInterface $query, $incomplete_key, $user_input) {
    if ($this->configuration['fields']) {
      $query->setFulltextFields($this->configuration['fields']);
    }

    if ($backend = static::getBackend($this->getIndex())) {
      return $backend->getAutocompleteSuggestions($query, $this->getSearch(), $incomplete_key, $user_input);
    }
    return NULL;
  }

  /**
   * Retrieves the backend for the given index, if it supports autocomplete.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return \Drupal\search_api_autocomplete\AutocompleteBackendInterface|null
   *   The backend plugin of the index's server, if it exists and supports
   *   autocomplete; NULL otherwise.
   */
  protected static function getBackend(IndexInterface $index) {
    if (!$index->hasValidServer()) {
      return NULL;
    }
    $server = $index->getServerInstance();
    $backend = $server->getBackend();
    if ($server->supportsFeature('search_api_autocomplete') || $backend instanceof AutocompleteBackendInterface) {
      return $backend;
    }
    return NULL;
  }

}
