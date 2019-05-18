<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_5_7
 *
 * API Class for the version 5.7.
 */
class SemanticConnectorSonrApi_5_7 extends SemanticConnectorSonrApi_5_6 {

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
    $config['spaceKey'] = '';

    $resource_path = $this->getApiPath() . 'agents/%id';

    $result = $this->connection->post($resource_path, array(
      'parameters' => array('%id' => $agent_id),
      'data' => Json::encode($config),
    ));

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
    $resource_path = $this->getApiPath() . 'agents/%id/delete';

    $result = $this->connection->post($resource_path, array(
      'parameters' => array('%id' => $agent_id),
      'data' => array(),
    ));

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

    $result = $this->connection->post($resource_path, array(
      'data' => array('id' => $agent_id),
      'timeout' => 120,
    ));

    return $result  === FALSE ? FALSE : TRUE;
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
    $resource_path = $this->getApiPath() . 'content/delete/all';

    $result = $this->connection->post($resource_path, array(
      'data' => array('source' => $source),
    ));

    return $result === FALSE ? FALSE : TRUE;
  }
}
