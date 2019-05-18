<?php

namespace Drupal\semantic_connector\Entity;
use Drupal\semantic_connector\Api\SemanticConnectorSparqlApi;
use EasyRdf_Exception;
use EasyRdf_Http;

/**
 * @ConfigEntityType(
 *   id ="sparql_endpoint_connection",
 *   label = @Translation("SPARQL endpoint connection"),
 *   handlers = {
 *     "list_builder" = "Drupal\semantic_connector\ConnectionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "add" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "edit" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "delete" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionDeleteForm"
 *     }
 *   },
 *   config_prefix = "sparql_endpoint_connection",
 *   admin_permission = "administer semantic connector",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/semantic-connector/connections/sparql-endpoint/{sparql_endpoint_connection}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/semantic-connector/connections/sparql-endpoint/{sparql_endpoint_connection}",
 *     "collection" = "/admin/config/semantic-drupal/semantic-connector/",
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "type",
 *     "url",
 *     "credentials",
 *     "config",
 *   }
 * )
 */
class SemanticConnectorSparqlEndpointConnection extends SemanticConnectorConnection {
  /**
   * The constructor of the SemanticConnectorSparqlEndpointConnection class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->type = 'sparql_endpoint';
  }

  /**
   * {@inheritdoc|}
   */
  public function available() {
    /** @var SemanticConnectorSparqlApi $sparql_client */
    $sparql_client = $this->getApi();
    $query = "
      SELECT *
      WHERE {
        ?s ?p ?o.
      }
      LIMIT 1";

    try {
      $row = $sparql_client->query($query);
    }
    catch (EasyRdf_Exception $e) {
      return FALSE;
    }

    return ($row->numRows() == 0) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc|}
   */
  public function getApi($api_type = '') {
    // Authorize if necessary.
    if (!empty($this->credentials['username'])) {
      $http_client = EasyRdf_Http::getDefaultHttpClient();

      // Use basic authentication, Digest is not supported by the way EasyRDF
      // currently works.
      $http_client->setHeaders('Authorization', 'Basic ' . base64_encode($this->credentials['username'] . ':' . $this->credentials['password']));
      EasyRdf_Http::setDefaultHttpClient($http_client);
    }

    return new SemanticConnectorSparqlApi($this->url);
  }

  /**
   * {@inheritdoc|}
   */
  public function getDefaultConfig() {
    return array(
      'pp_server_id' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function exist($id) {
    $entity_count = \Drupal::entityQuery('sparql_endpoint_connection')
      ->condition('id', $id)
      ->count()
      ->execute();
    return (bool) $entity_count;
  }
}