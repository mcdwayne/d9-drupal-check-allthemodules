<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_6_1
 *
 * API Class for the version 6.1.
 */
class SemanticConnectorSonrApi_6_1 extends SemanticConnectorSonrApi_6_0 {
  /**
   * This method checks if the GraphSearch service exists and is running.
   *
   * @return bool
   *   TRUE if the service is available, FALSE if not
   */
  public function available() {
    $resource_path = $this->getApiPath() . 'heartbeat';
    $result = $this->connection->get($resource_path);

    return $result === '' ? TRUE : FALSE;
  }

  /**
   * This method gets the configuration of the PoolParty GraphSearch server.
   *
   * @return boolean|array
   *   project => The PoolParty project used for the extraction
   *   language => The configured language of the content.
   */
  public function getConfig() {
    $resource_path = $this->getApiPath() . 'context/all';
    $result = $this->connection->get($resource_path);

    if ($result === FALSE) {
      return FALSE;
    }

    $data = Json::decode($result);

    $projects = array();
    foreach ($data as $searchSpace) {
      if (strtolower($searchSpace['integrationType']) != 'drupal') {
        continue;
      }
      foreach ($searchSpace['facetIds'] as $facetId) {
        if ($facetId['thesaurus']) {
          if (!isset($projects[$facetId['facetId']])) {
            $projects[$facetId['facetId']] = array(
              'id' => $facetId['facetId'],
              'search_spaces' => array(),
            );
          }
          $projects[$facetId['facetId']]['search_spaces'][$searchSpace['searchSpaceId']] = array(
            'id' => $searchSpace['searchSpaceId'],
            'name' => $searchSpace['searchSpaceName'],
            'language' => $searchSpace['defaultLanguage'],
          );
        }
      }
    }

    return array(
      'projects' => $projects
    );
  }

  /**
   * This method gets the field configuration of the PoolParty GraphSearch
   * server.
   *
   * @param string $search_space_id
   *   The ID of the search space to get the field config for.
   *
   * @return boolean|array
   *   searchFields -> all available fields in a list
   *   contextSearchFields -> all fields grouped by field context.
   */
  public function getFieldConfig($search_space_id = '') {
    $resource_path = $this->getApiPath() . 'context/fields';

    $get_parameters = array();
    if (!empty($search_space_id)) {
      $get_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->get($resource_path, [
      'query' => $get_parameters,
    ]);
    $facet_list = Json::decode($result);

    if (!is_array($facet_list)) {
      return FALSE;
    }

    // Make compatible with older version
    if (!empty($facet_list['searchFields'])) {
      foreach ($facet_list['searchFields'] as &$search_fields) {
        $search_fields['name'] = $search_fields['field'];
        $search_fields['type'] = $search_fields['searchFieldType'];
        if (isset($search_fields['sortDirection'])) {
          $search_fields['defaultSortDirection'] = $search_fields['sortDirection'];
        }
      }
    }

    return $facet_list;
  }

  /**
   * This method searches in the GraphSearch index.
   *
   * @param string $search_space_id
   *   The search space to use for the search.
   * @param array $facets
   *   A list of facet objects that should be used for faceting the
   *   search. [optional]
   * @param array $filters
   *   A list of filter object parameters that define the query. [optional]
   *    array(
   *      object(
   *        'field'    => (string)  facedID   | 'date-to' | 'date-from',
   *        'value'    => (int)     conceptID | timestamp | timestamp,
   *        'optional' => (boolean) TRUE, (default: TRUE)
   *      ),
   *      ...
   *    )
   * @param array $parameters
   *   A list of key value pairs [optional]
   *    array(
   *      'facetMinCount' => (int)    1,     (default:    1)
   *      'locale'        => (string) 'en',  (default: 'en')
   *      'start'         => (int)    0,     (default:    0)
   *      'count'         => (int)    10,    (default:   10)
   *      'sort'          => object(
   *        'field'     => (string) facetID | 'date',
   *        'direction' => (string) 'DESC' | 'ASC',
   *      ),   (default: object('field' => 'date', 'direction' => 'DESC')
   *    )
   *
   * @return boolean|array
   *   List of items or FALSE in case of an error
   */
  public function search($search_space_id = '', $facets = [], $filters = [], $parameters = []) {

    $resource_path = $this->getApiPath() . 'search';

    $sort = new \stdClass();
    $sort->field = 'date';
    $sort->direction = 'DESC';

    $post_parameters = [
      'searchFacets' => $this->prepareFacets($facets),
      'searchFilters' => $this->prepareFilters($filters),
      'documentFacets' => $this->customAttributes,
      'facetMinCount' => isset($parameters['facetMinCount']) ? $parameters['facetMinCount'] : 1,
      'maxFacetCount' => isset($parameters['maxFacetCount']) ? $parameters['maxFacetCount'] : 10,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'start' => isset($parameters['start']) ? $parameters['start'] : 0,
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'sort' => isset($parameters['sort']) ? $parameters['sort'] : $sort,
    ];

    if (!empty($search_space_id)) {
      $post_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'data' => Json::encode($post_parameters),
    ]);

    $items = Json::decode($result);

    if (!is_array($items)) {
      return FALSE;
    }

    // Make compatible with older version
    $items = $this->makeSearchCompatible($items);

    return $items;
  }

  /**
   * Get concept suggestions from a given search string.
   *
   * @param string $search_string
   *   The string to get suggestions for
   * @param string $search_space_id
   *   The ID of the search space to use for the suggestions.
   * @param array $parameters
   *   array(
   *    'locale' => (string) 'en',  (default: 'en')
   *    'count'  => (int)    10,    (default:   10)
   *  )
   *
   * @return boolean|array
   *   Array of concepts
   *   array(
   *    'id'      => (string) URI of concept
   *    'label'   => (string) prefLabel of concept
   *    'context' => (string) prefLabel of the broader concept
   *    'field'   => (string) URI of conceptScheme
   *  )
   */
  public function suggest($search_string, $search_space_id = '', $parameters = []) {
    $resource_path = $this->getApiPath() . 'suggest/multi';

    $get_parameters = array_merge([
      'searchString' => $search_string,
      'locale' => 'en',
      'count' => 10,
    ], $parameters);

    if (!empty($search_space_id)) {
      $get_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->get($resource_path, [
      'query' => $get_parameters,
    ]);

    $concepts = Json::decode($result);

    if (!is_array($concepts)) {
      return FALSE;
    }

    // Make compatible with older version
    $concepts = $this->makeSuggestCompatible($concepts);

    return $concepts;
  }

  /**
   * Get similar content.
   *
   * @param int $item_id
   *   The uri of the item
   * @param string $search_space_id
   *   The ID of the search to use to get similar content.
   * @param array $parameters
   *   Array of the parameters
   *
   * @return boolean|array
   *   A key value pair list of facets or FALSE in case of an error
   */
  public function getSimilar($item_id, $search_space_id = '', $parameters = []) {
    $resource_path = $this->getApiPath() . 'similar';
    $get_parameters = array_merge([
      'id' => $item_id,
      'locale' => 'en',
      'count' => 10,
      'fields' => 'dyn_uri_all_concepts,title,description',
      'documentFacets' => implode(",", $this->customAttributes),
    ], $parameters);

    if (!empty($search_space_id)) {
      $get_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->get($resource_path, [
      'query' => $get_parameters,
    ]);

    $similar = Json::decode($result);
    if (!is_array($similar)) {
      return FALSE;
    }

    // Make compatible with older version
    $similar = $this->makeSearchCompatible($similar);

    return $similar;
  }

  /**
   * Get the concepts, free terms and recommended content for a given text.
   *
   * @param string $text
   *   The text for the recommendation.
   * @param string $search_space_id
   *   The ID of the search space to use for the recommendation.
   * @param array $parameters
   *   array(
   *     'language' => (string) 'en', (default: 'en')
   *   )
   *
   * @return boolean|array
   *   List of concepts, free terms and recommended content or FALSE in case of
   *   an error
   */
  public function getRecommendation($text, $search_space_id = '', $parameters = []) {
    $resource_path = $this->getApiPath() . 'recommend';
    $post_parameters = array_merge([
      'text' => $text,
      'locale' => 'en',
      'start' => 0,
      'count' => 10,
      'numberOfConcepts' => 10,
      'numberOfTerms' => 5,
      'fields' => ['dyn_uri_all_concepts', 'title', 'description'],
    ], $parameters);

    if (!empty($search_space_id)) {
      $post_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'data' => Json::encode($post_parameters),
    ]);

    $recommendations = Json::decode($result);

    if (!is_array($recommendations)) {
      return FALSE;
    }
    else {
      // property 'prefLabel' is named 'label' now --> add prefLabel for
      // backwards compatibility.
      if (isset($recommendations['concepts'])) {
        foreach ($recommendations['concepts'] as &$annotation) {
          if (!isset($annotation['prefLabel'])) {
            $annotation['prefLabel'] = $annotation['label'];
          }
          unset($annotation);
        }
      }
    }

    return $recommendations;
  }

  /**
   * Get all agents with their configuration and status.
   *
   * @param string $search_space_id
   *   The ID of the search to get the agents for.
   *
   * @return boolean|array
   *   A list of agents with their configuration and status
   */
  public function getAgents($search_space_id = '') {
    $resource_path = $this->getApiPath() . 'agents/status';

    $get_parameters = [];
    if (!empty($search_space_id)) {
      $get_parameters['searchSpaceId'] = $search_space_id;
    }
    $result = $this->connection->get($resource_path, [
      'query' => $get_parameters,
    ]);

    $agent_list = Json::decode($result);

    if (!is_array($agent_list)) {
      return FALSE;
    }

    $agents = [];
    if (!is_null($agent_list)) {
      foreach ($agent_list as $id => $agent) {
        $agents[$id] = new \stdClass();
        $agents[$id]->id = $agent['agent']['id'];
        $agents[$id]->configuration = $agent['agent']['configuration'];
        $agents[$id]->status = $agent['status'];
      }
      usort($agents, [$this, 'sortAgents']);
    }

    return $agents;
  }

  /**
   * Get one agent with his configuration.
   *
   * @param int $agent_id
   *   The ID of the agent
   * @param string $search_space_id
   *   The ID of the search to get the agent for.
   *
   * @return boolean|array
   *   The configuration of a given agent or FALSE in case of an error
   */
  public function getAgent($agent_id, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'agents/config';

    $get_parameters = [
      'id' => $agent_id,
    ];
    if (!empty($search_space_id)) {
      $get_parameters['searchSpaceId'] = $search_space_id;
    }
    $result = $this->connection->get($resource_path, [
      'query' => $get_parameters,
    ]);

    $agent = Json::decode($result);

    if (!is_array($agent)) {
      return FALSE;
    }

    $agent['id'] = $agent_id;

    return $agent;
  }

  /**
   * Add a new agent.
   *
   * @param array $config
   *   array(
   *    'source'          => (string) 'My Source',
   *    'url'             => (string) 'http://example.com/rss.xml'
   *    'username'        => (string) 'admin',
   *    'periodMillis'    => (int) 3600000,
   *   )
   * @param string $search_space_id
   *   The ID of the search to create the agent for.
   *
   * @return bool
   *   TRUE on success, FALSE on error
   */
  public function addAgent($config, $search_space_id = '') {
    $config['privateContent'] = FALSE;
    $config['context'] = '';

    if (!empty($search_space_id)) {
      $config['searchSpaceId'] = $search_space_id;
    }

    $resource_path = $this->getApiPath() . 'agents/create';

    $result = $this->connection->post($resource_path, [
      'data' => Json::encode($config),
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Update an agent.
   *
   * @param int $agent_id
   *   The ID of the agent.
   * @param array $config
   *   array(
   *    'source'          => (string) 'My Source',
   *    'url'             => (string) 'http://example.com/rss.xml'
   *    'username'        => (string) 'admin',
   *    'periodMillis'    => (int) 3600000,
   *   )
   * @param string $search_space_id
   *   The ID of the search space the agent was created for.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function updateAgent($agent_id, $config, $search_space_id = '') {
    $config['privateContent'] = FALSE;
    $config['context'] = '';

    if (!empty($search_space_id)) {
      $config['searchSpaceId'] = $search_space_id;
    }

    $resource_path = $this->getApiPath() . 'agents/update';

    $result = $this->connection->post($resource_path, [
      'data' => Json::encode($config),
      'query' => ['id' => $agent_id],
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Delete an agent.
   *
   * @param int $agent_id
   *   The ID of the agent.
   * @param string $search_space_id
   *   The ID of the search space the agent was created for.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function deleteAgent($agent_id, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'agents/delete';

    $query_parameters = [
      'id' => $agent_id,
    ];
    if (!empty($search_space_id)) {
      $query_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'query' => $query_parameters,
      'data' => '',
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Run an agent.
   *
   * @param int $agent_id
   *   The ID of the agent.
   * @param string $search_space_id
   *   The ID of the search space the agent was created for.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function runAgent($agent_id, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'agents/runAgent';

    $query_parameters = [
      'id' => $agent_id,
    ];
    if (!empty($search_space_id)) {
      $query_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'query' => $query_parameters,
      'data' => '',
      'timeout' => 120,
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Delete all indexed documents from an agent.
   *
   * @param string $source
   *   The name of the source.
   * @param string $search_space_id
   *   The ID of the search space to delete documents from.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function deleteIndex($source, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'content/delete/source';

    $post_parameters = [
      'source' => $source
    ];
    if (!empty($search_space_id)) {
      $post_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'data' => $post_parameters,
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Update an existing ping.
   *
   * @param array $ping
   *   array(
   *    'title'         => (string) Title of the ping
   *    'text'          => (string) Content of the ping
   *    'username'      => (string) 'admin',
   *    'creationDate'  => (int) unix timestamp,
   *    'pageUrl'       => (string) node URL --> will become the ID,
   *    'spaceKey'      => (string) 'extern', ... not relevant for Drupal.
   *    'dynUris'{      => (object) Tags of the content and term references
   *      'dyn_uri_all_concepts": [
   *        'http://server.com/Project/Concept1',
   *        'http://server.com/Project/Concept2',
   *        'http://server.com/Project/Concept3'
   *        ]
   *    }
   *  )
   * @param string $search_space_id
   *   The ID of the search space to update the ping in.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function updatePing(array $ping, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'content/update';
    $ping['identifier'] = $ping['pageUrl'];
    $ping['text'] = substr($ping['text'], 0, 12000);
    $ping['author'] = $ping['username'];
    $ping['date'] = $ping['creationDate'];
    $ping['facets'] = array_merge($ping['customAttributes'], $ping['dynUris']);
    $ping['useExtraction'] = empty($ping['dynUris']);
    $ping['context'] = $ping['spaceKey'];

    if (!empty($search_space_id)) {
      $ping['searchSpaceId'] = $search_space_id;
    }

    unset($ping['pageUrl']);
    unset($ping['username']);
    unset($ping['creationDate']);
    unset($ping['customAttributes']);
    unset($ping['dynUris']);
    unset($ping['spaceKey']);

    $result = $this->connection->post($resource_path, [
      'data' => Json::encode($ping),
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Delete an existing ping.
   *
   * @param string $page
   *   The URL of the page (= ID of the ping).
   * @param string $search_space_id
   *   The ID of the search space to delete the ping in.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function deletePing($page, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'content/delete/id';

    $post_parameters = [
      'identifier' => $page
    ];
    if (!empty($search_space_id)) {
      $post_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'data' => Json::encode($post_parameters),
    ]);

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Adds a new custom search field for the suggestion call.
   *
   * @param string $label
   *   The label of the custom search field.
   * @param string $field
   *   The name of the custom search field, e.g. 'content_type'.
   * @param string $search_space_id
   *   The ID of the search space to add the custom search field for.
   *
   * @return boolean
   *   TRUE if field is added, otherwise FALSE.
   */
  public function addCustomSearchField($label, $field, $search_space_id = '') {
    $resource_path = '/' . $this->graphSearchPath . '/admin/suggest/add';
    $field = 'dyn_lit_' . str_replace('-', '_', $field);
    $post_parameters = [
      'field' => $field,
      'label' => $label,
    ];
    if (!empty($search_space_id)) {
      $post_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'data' => $post_parameters,
    ]);

    if ($result !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Deletes a custom search field for the suggestion call.
   *
   * @param string $field
   *   The name of the custom search field, e.g. 'content_type'.
   * @param string $search_space_id
   *   The ID of the search space to delete the custom search field for.
   *
   * @return boolean
   *   TRUE if field is deleted, otherwise FALSE.
   */
  public function deleteCustomSearchField($field, $search_space_id = '') {
    $resource_path = '/' . $this->graphSearchPath . '/admin/suggest/delete';
    $field = 'dyn_lit_' . str_replace('-', '_', $field);
    $post_parameters = [
      'field' => $field,
    ];
    if (!empty($search_space_id)) {
      $post_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->post($resource_path, [
      'data' => $post_parameters,
    ]);

    if ($result !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get trends from a list of concepts.
   *
   * @param array $uris
   *   A list of uris of concepts.
   * @param string $search_space_id
   *   The search space to get the trends for.
   *
   * @return boolean|array
   *   List of trends.
   */
  public function getTrends($uris, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'trend/histories';

    if (is_string($uris)) {
      $uris = [$uris];
    }

    $get_parameters = [
      'concepts' => implode(',', $uris)
    ];
    if (!empty($search_space_id)) {
      $get_parameters['searchSpaceId'] = $search_space_id;
    }

    $result = $this->connection->get($resource_path, [
      'query' => $get_parameters,
    ]);

    $trends = Json::decode($result);

    if (!is_array($trends)) {
      return FALSE;
    }

    return $trends;
  }
}