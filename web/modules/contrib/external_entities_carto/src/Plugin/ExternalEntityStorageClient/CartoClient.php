<?php

namespace Drupal\external_entities_carto\Plugin\ExternalEntityStorageClient;

use Drupal\external_entities\ExternalEntityStorageClientBase;

/**
 * CARTO implementation of an external entity storage client.
 *
 * @ExternalEntityStorageClient(
 *   id = "carto_client",
 *   name = "CARTO"
 * )
 */
class CartoClient extends ExternalEntityStorageClientBase {

  /**
   * {@inheritdoc}
   */
  public function delete(\Drupal\external_entities\ExternalEntityInterface $entity) {
    $query = 'DELETE FROM ' . $this->configuration['endpoint'] . ' WHERE cartodb_id = ' . $entity->externalId();

    $response = $this->cartoExecuteQuery($query);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $query = 'SELECT * FROM ' . $this->configuration['endpoint'] . ' WHERE cartodb_id = ' . $id;
    $response = $this->cartoExecuteQuery($query);

    return (object) $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody())['rows'][0];
  }

  /**
   * {@inheritdoc}
   */
  public function save(\Drupal\external_entities\ExternalEntityInterface $entity) {
    if ($entity->externalId()) {
      $fields = [];
      foreach ($entity->getMappedObject() as $key => $value) {
        if ($key == 'the_geom') {
          $fields[] = "the_geom = ST_GeomFromText('$value', 4326)";
        }
        elseif (is_numeric($value)) {
          $fields[] = "$key = $value";
        }
        else {
          $fields[] = "$key = '$value'";
        }
      }

      $query = 'UPDATE ' . $this->configuration['endpoint'] . ' SET ' . implode(', ', $fields) . 'WHERE cartodb_id = ' . $entity->externalId();
      $this->cartoExecuteQuery($query);

      $object = $this->load($entity->externalId());
      $result = SAVED_UPDATED;
    }
    else {
      $keys = $fields = [];
      foreach ($entity->getMappedObject() as $key => $value) {
        if ($key == 'cartodb_id') {
          continue;
        }
        $keys[] = $key;
        if ($key == 'the_geom') {
          $fields[] = "ST_GeomFromText('$value', 4326)";
        }
        elseif (is_numeric($value)) {
          $fields[] = "$value";
        }
        else {
          $fields[] = "'$value'";
        }
      }

      $query = 'INSERT INTO ' . $this->configuration['endpoint'] . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $fields) .')  RETURNING cartodb_id';
      $response = $this->cartoExecuteQuery($query);
      $id = $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody())['rows'][0]['cartodb_id'];
      $object = $this->load($id);
      $result = SAVED_NEW;
    }

    $entity->mapObject($object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {
    $query = 'SELECT * FROM ' . $this->configuration['endpoint'];
    if ($parameters) {
      $query .= ' LIMIT ' . $parameters['pagesize'] . ' OFFSET ' . $parameters['page'];
    }

    $response = $this->cartoExecuteQuery($query);

    $results = $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody())['rows'];
    foreach ($results as &$result) {
      $result = ((object) $result);
    }
    return $results;
  }

  /**
   * @param string $query
   *
   * @return \GuzzleHttp\Psr7\Response
   */
  protected function cartoExecuteQuery($query) {
    $headers = $this->getHttpHeaders();
    $response = $this->httpClient->get(
      'https://' . key($headers) . '.carto.com/api/v2/sql',
      [
        'query' => [
          'q' => $query,
          'api_key' => reset($headers)
        ]
      ]
    );
    return $response;
  }

}
