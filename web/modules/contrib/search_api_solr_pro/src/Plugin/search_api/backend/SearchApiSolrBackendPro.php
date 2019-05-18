<?php

namespace Drupal\search_api_solr_pro\Plugin\search_api\backend;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Drupal\search_api_solr\Utility\Utility as SearchApiSolrUtility;
use Drupal\search_api_solr_pro\Item\ItemPro;
use Solarium\Core\Client\Response;
use Solarium\Core\Query\Result\ResultInterface;


/**
 * Apache Solr backend Pro for search api.
 *
 * @SearchApiBackend(
 *   id = "search_api_solr_pro",
 *   label = @Translation("Solr Pro"),
 *   description = @Translation("Index items using an Apache Solr search server with extra features.")
 * )
 */
class SearchApiSolrBackendPro extends SearchApiSolrBackend {

  private function _getItemById($id_field, array $doc_fields, QueryInterface $query) {
    $index = $query->getIndex();
    $datasourceId = explode('/', $doc_fields[$id_field])[0];
    $datasource = $index->getDatasource($datasourceId);
    // For items coming from a different site, we need to adapt the item ID.
    $item_id = ((!$this->configuration['site_hash'] && $doc_fields['hash'] != SearchApiSolrUtility::getSiteHash())
      ? $doc_fields['hash'] . '--'
      : '') . $doc_fields[$id_field];
    return ($query->getOption('search_api_avoid_load_entity')) ? new ItemPro($doc_fields[$id_field], $datasource) : $this->fieldsHelper->createItem($index, $item_id);
  }

  /**
   * Extract results from a Solr response.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The Search API query object.
   * @param \Solarium\Core\Query\Result\ResultInterface $result
   *   A Solarium select response object.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   A result set object.
   */
  protected function extractResults(QueryInterface $query, ResultInterface $result) {
    $index = $query->getIndex();
    $backend_config = $index->getServerInstance()->getBackendConfig();
    $field_names = $this->getSolrFieldNames($index);
    $fields = $index->getFields(TRUE);

    // We can find the item ID and the score in the special 'search_api_*'
    // properties. Mappings are provided for these properties in
    // SearchApiSolrBackend::getFieldNames().
    $id_field = $field_names['search_api_id'];
    $score_field = $field_names['search_api_relevance'];

    // Set up the results array.
    $result_set = $query->getResults();
    $result_set->setExtraData('search_api_solr_response', $result->getData());

    // In some rare cases (e.g., MLT query with nonexistent ID) the response
    // will be NULL.
    $is_grouping = $result instanceof Result && $result->getGrouping();
    if (!$result->getResponse() && !$is_grouping) {
      $result_set->setResultCount(0);
      return $result_set;
    }

    // If field collapsing has been enabled for this query, we need to process
    // the results differently.
    $grouping = $query->getOption('search_api_grouping');
    $docs = array();
    if (!empty($grouping['use_grouping']) && $is_grouping) {
    } else {
      $result_set->setResultCount($result->getNumFound());
      $docs = $result->getDocuments();
    }

    // Add each search result to the results array.
    /** @var \Solarium\QueryType\Select\Result\Document $doc */
    foreach ($docs as $doc) {
      $doc_fields = $doc->getFields();
      $result_item = $this->_getItemById($id_field, $doc_fields, $query);
      $result_item->setExtraData('search_api_solr_document', $doc);
      $result_item->setScore($doc_fields[$score_field]);
      unset($doc_fields[$id_field], $doc_fields[$score_field]);

      // Extract properties from the Solr document, translating from Solr to
      // Search API property names. This reverses the mapping in
      // SearchApiSolrBackend::getFieldNames().
      foreach ($field_names as $search_api_property => $solr_property) {
        if (isset($doc_fields[$solr_property]) && isset($fields[$search_api_property])) {
          $doc_field = is_array($doc_fields[$solr_property]) ? $doc_fields[$solr_property] : [$doc_fields[$solr_property]];
          $field = clone $fields[$search_api_property];
          foreach ($doc_field as &$value) {
            switch ($field->getType()) {
              case 'date':
                // Field type convertions
                // Date fields need some special treatment to become valid date values
                // (i.e., timestamps) again.
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
                  $value = strtotime($value);
                }
                break;

              case 'text':
                $value = new TextValue($value);
            }
          }
          $field->setValues($doc_field);
          $result_item->setField($search_api_property, $field);
        }
      }

      if (!empty($backend_config['retrieve_data'])) {
        $solr_id = $this->createId($index->id(), $result_item->getId());
        $excerpt = $this->getExcerpt($result->getData(), $solr_id, $result_item, $field_names);
        if ($excerpt) {
          $result_item->setExcerpt($excerpt);
        }
      }
      $result_set->addResultItem($result_item);
    }

    return $result_set;
  }

  /**
   * {@inheritdoc}
   *
   * Options on $query prefixed by 'solr_param_' will be passed natively to Solr
   * as query parameter without the prefix. For example you can set the "Minimum
   * Should Match" parameter 'mm' to '75%' like this:
   * @code
   *   $query->setOption('solr_param_mm', '75%');
   * @endcode
   */
  public function search(QueryInterface $query) {
    $mlt_options = $query->getOption('search_api_mlt');
    if (!empty($mlt_options)) {
      $query->addTag('mlt');
    }

    // Call an object oriented equivalent to hook_search_api_query_alter().
    $this->alterSearchApiQuery($query);

    // Get field information.
    /** @var \Drupal\search_api\Entity\Index $index */
    $index = $query->getIndex();
    $index_id = $this->getIndexId($index->id());
    $field_names = $this->getSolrFieldNames($index);

    $connector = $this->getSolrConnector();
    $solarium_query = NULL;
    $index_fields = $index->getFields();
    $index_fields += $this->getSpecialFields($index);
    if ($query->hasTag('mlt')) {
      $solarium_query = $this->getMoreLikeThisQuery($query, $index_id, $index_fields, $field_names);
    }
    else {
      // Instantiate a Solarium select query.
      $solarium_query = $connector->getSelectQuery();

      // Extract keys.
      $keys = $query->getKeys();
      if (is_array($keys)) {
        $keys = $this->flattenKeys($keys);
      }

      if (!empty($keys)) {
        // Set them.
        $solarium_query->setQuery($keys);
      }

      // Set searched fields.
      $search_fields = $this->getQueryFulltextFields($query);
      $query_fields = [];
      $query_fields_boosted = [];
      foreach ($search_fields as $search_field) {
        $query_fields[] = $field_names[$search_field];
        /** @var \Drupal\search_api\Item\FieldInterface $field */
        $field = $index_fields[$search_field];
        $boost = $field->getBoost() ? '^' . $field->getBoost() : '';
        $query_fields_boosted[] = $field_names[$search_field] . $boost;
      }
      $solarium_query->getEDisMax()
        ->setQueryFields(implode(' ', $query_fields_boosted));

      if (!empty($this->configuration['retrieve_data'])) {
        // Set highlighting.
        $this->setHighlighting($solarium_query, $query, $query_fields);
      }
    }

    $options = $query->getOptions();

    // Set basic filters.
    $filter_queries = $this->getFilterQueries($query, $field_names, $index_fields, $options);
    foreach ($filter_queries as $id => $filter_query) {
      $solarium_query->createFilterQuery('filters_' . $id)
        ->setQuery($filter_query['query'])
        ->addTags($filter_query['tags']);
    }

    $query_helper = $connector->getQueryHelper($solarium_query);
    // Set the Index filter.
    $solarium_query->createFilterQuery('index_id')->setQuery('index_id:' . $query_helper->escapePhrase($index_id));

    // Set the site hash filter, if enabled.
    if ($this->configuration['site_hash']) {
      $site_hash = $query_helper->escapePhrase(SearchApiSolrUtility::getSiteHash());
      $solarium_query->createFilterQuery('site_hash')->setQuery('hash:' . $site_hash);
    }

    // @todo Make this more configurable so that Search API can choose which
    //   fields it wants to fetch.
    //   @see https://www.drupal.org/node/2880674
    if (!empty($this->configuration['retrieve_data'])) {
      $solarium_query->setFields(['*', 'score']);
    }
    else {
      $returned_fields = [SEARCH_API_ID_FIELD_NAME, 'score'];
      if (!$this->configuration['site_hash']) {
        $returned_fields[] = 'hash';
      }
      $solarium_query->setFields($returned_fields);
    }

    // Set sorts.
    $this->setSorts($solarium_query, $query, $field_names);

    // Set facet fields. setSpatial() might add more facets.
    $this->setFacets($query, $solarium_query, $field_names);

    // Handle spatial filters.
    if (isset($options['search_api_location'])) {
      $this->setSpatial($solarium_query, $options['search_api_location'], $field_names);
    }

    // Handle field collapsing / grouping.
    $grouping_options = $query->getOption('search_api_grouping');
    if (!empty($grouping_options['use_grouping'])) {
      $this->setGrouping($solarium_query, $query, $grouping_options, $index_fields, $field_names);
    }

    if (isset($options['offset'])) {
      $solarium_query->setStart($options['offset']);
    }
    $rows = isset($options['limit']) ? $options['limit'] : 1000000;
    $solarium_query->setRows($rows);

    if (!empty($options['search_api_spellcheck'])) {
      $solarium_query->getSpellcheck();
    }

    foreach ($options as $option => $value) {
      if (strpos($option, 'solr_param_') === 0) {
        $solarium_query->addParam(substr($option, 11), $value);
      }
    }

    $this->applySearchWorkarounds($solarium_query, $query);

    try {
      // Allow modules to alter the solarium query.
      $this->moduleHandler->alter('search_api_solr_query', $solarium_query, $query);
      $this->preQuery($solarium_query, $query);

      //adding view fields to solr query only if entity should not be load
      if ($query->getOption('search_api_avoid_load_entity')) {
        $add_fields = $query->getOption('search_api_add_fields');
        $solr_fields = $this->getSolrFieldNames($query->getIndex());
        $solarium_query->addFields(array_reduce($add_fields, function($added, $index) use ($solr_fields) {
          if ($solr_fields[$index]) $added[] = $solr_fields[$index];
          return $added;
        }, []));
      }

      // Send search request.
      $response = $connector->search($solarium_query);
      $body = $response->getBody();
      if (200 != $response->getStatusCode()) {
        throw new SearchApiSolrException(strip_tags($body), $response->getStatusCode());
      }
      $this->alterSolrResponseBody($body, $query);
      $response = new Response($body, $response->getHeaders());

      $result = $connector->createSearchResult($solarium_query, $response);

      // Extract results.
      $results = $this->extractResults($query, $result);

      // Add warnings, if present.
      if (!empty($warnings)) {
        foreach ($warnings as $warning) {
          $results->addWarning($warning);
        }
      }

      // Extract facets.
      if ($result instanceof Result) {
        if ($facets = $this->extractFacets($query, $result, $field_names)) {
          $results->setExtraData('search_api_facets', $facets);
        }
      }

      $this->moduleHandler->alter('search_api_solr_search_results', $results, $query, $result);
      $this->postQuery($results, $query, $result);
    }
    catch (\Exception $e) {
      throw new SearchApiSolrException($this->t('An error occurred while trying to search with Solr: @msg.', array('@msg' => $e->getMessage())), $e->getCode(), $e);
    }
  }
}

