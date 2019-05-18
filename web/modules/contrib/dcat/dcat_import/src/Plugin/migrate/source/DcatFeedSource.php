<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\dcat_import\Plugin\DcatGraph;
use EasyRdf_Graph;
use EasyRdf_Resource;

/**
 * DCAT feed source.
 */
abstract class DcatFeedSource extends SourcePluginBase {

  /**
   * Array of source data.
   *
   * @var array.
   */
  private $sourceData;

  /**
   * Bool to indicate if the extractor has already ran.
   *
   * @var bool
   */
  private $extractionDone = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $cacheCounts = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $trackChanges = TRUE;

  /**
   * Returns the DCAT type to extract from the feed.
   *
   * E.g. dcat:Dataset, dcat:Distribution ...
   *
   * @return string
   *   The DCAT type to extract.
   */
  public abstract function getDcatType();

  /**
   * Extract data from the given EasyRdf Graph.
   *
   * @param EasyRdf_Graph $graph
   *   The EasyRdf Graph to extract the data from.
   *
   * @return array
   *   The extracted data.
   */
  public function getDcatData(EasyRdf_Graph $graph) {
    $data = $graph->allOfType($this->getDcatType());
    return $graph->getNoneBlankResources($data);
  }

  /**
   * Data getter.
   *
   * @return array
   *   Array of source data.
   */
  public function getSourceData() {
    if (!$this->extractionDone) {
      $format = isset($this->configuration['format']) ? $this->configuration['format'] : 'turtle';
      $pager_argument = isset($this->configuration['pager_argument']) ? $this->configuration['pager_argument'] : NULL;

      $graph = DcatGraph::newAndLoad($this->configuration['uri'], $format, $pager_argument);
      $data = $this->getDcatData($graph);
      $deleted = $this->deletedResources($data, $graph);

      $this->sourceData = array_merge($data, $deleted);
      $this->extractionDone = TRUE;
    }

    return $this->sourceData;
  }

  /**
   * Returns the deleted resources so they can be unpublished.
   *
   * @param array $data
   *   The current array of data resources.
   * @param DcatGraph $graph
   *   The graph used to create the deleted resources.
   *
   * @return array
   *   An array of EasyRdf resources that are deleted in the current graph.
   */
  private function deletedResources(array $data, DcatGraph $graph) {
    $deleted = [];
    /** @var Sql $map */
    $map = $this->migration->getIdMap();

    $imported = $map->getDatabase()->select($map->mapTableName(), 'map')
      ->fields('map', ['sourceid1'])
      ->execute()
      ->fetchAllKeyed(0, 0);

    foreach ($data as $resource) {
      if (!is_object($resource)) {
        return [];
      }
      /** @var EasyRdf_Resource $uri */
      $uri = $resource->getUri();
      unset($imported[(string) $uri]);
    }

    foreach ($imported as $uri => $uuid) {
      $resource = $graph->resource($uri);
      $resource->add('deleted', 1);
      $deleted[] = $resource;
    }

    return $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $data = [];

    foreach ($this->getSourceData() as $resource) {
      $data[] = $this->convertResource($resource);
    }

    return new \ArrayIterator($data);
  }

  /**
   * Convert an EasyRdf resource to an array.
   *
   * @param \EasyRdf_Resource $resource
   *   The resource to covert.
   *
   * @return array
   *   Array of values to import.
   */
  public function convertResource(EasyRdf_Resource $resource) {
    return [
      'uri' => $resource->getUri(),
      'status' => !$this->getValue($resource, 'deleted'),
    ];
  }

  /**
   * Allows class to decide how it will react when it is treated like a string.
   */
  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    return count($this->getSourceData());
  }

  /**
   * Unify the return values.
   *
   * @param mixed $value
   *   The value to unify.
   *
   * @return null|string|array
   *   Null if empty, string if single value, array if multi value.
   */
  public function unifyReturnValue($value) {
    if (empty($value)) {
      return NULL;
    }
    if (!is_array($value)) {
      return $value;
    }
    if (count($value) == 1) {
      return array_shift($value);
    }

    return $value;
  }

  /**
   * Return all values for a property from an EasyRdf resource.
   *
   * @param \EasyRdf_Resource $resource
   *   The EasyRdf resource to get the property from.
   * @param string $property
   *   The name of the property to get.
   *
   * @return array
   *   The values as an array of strings.
   */
  public function getValueArray(EasyRdf_Resource $resource, $property) {
    $values = array();

    foreach ($resource->all($property) as $value) {
      if (!empty($value)) {
        $values[] = $this->getSingleValue($value);
      }
    }

    return $values;
  }

  /**
   * Convert an EasyRdf Resource or Literal to a single value.
   *
   * @param mixed $value
   *   EasyRdf_Resource or EasyRdf_Literal object.
   *
   * @return string|null
   *   A single value representing the object or Null if it is a blank resource.
   */
  public function getSingleValue($value) {
    $class = get_class($value);
    switch ($class) {
      case 'EasyRdf_Resource':
        if ($value->isBNode()) {
          return NULL;
        }
        return $value->getUri();

      case 'EasyRdf_Literal_DateTime':
        return $value->getValue()->format('c');

      default:
        return $value->getValue();
    }
  }

  /**
   * Get the value for a property from an EasyRdf resource.
   *
   * @param \EasyRdf_Resource $resource
   *   The EasyRdf resource to get the property from.
   * @param string $property
   *   The name of the property to get.
   *
   * @return null|string|array
   *   Null if empty, string if single value, array if multi value.
   */
  public function getValue(EasyRdf_Resource $resource, $property) {
    $values = $this->getValueArray($resource, $property);
    return $this->unifyReturnValue($values);
  }

  /**
   * Get a certain property from an EasyRdf resource as datetime storage string.
   *
   * @param \EasyRdf_Resource $resource
   *   The EasyRdf resource to get the property from.
   * @param string $property
   *   The name of the property to get.
   *
   * @return null|string|array
   *   Null if empty, string if single value, array if multi value.
   */
  public function getDateValue(EasyRdf_Resource $resource, $property) {
    $values = $this->getValueArray($resource, $property);

    $dates = array();
    foreach ($values as $value) {
      $date = $value instanceof \DateTime ? DrupalDateTime::createFromDateTime($value) : new DrupalDateTime($value);
      $dates[] = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }

    return $this->unifyReturnValue($dates);
  }

  /**
   * Get a certain property from an EasyRdf resource as an email string.
   *
   * Basically removes mailto: part.
   *
   * @param \EasyRdf_Resource $resource
   *   The EasyRdf resource to get the property from.
   * @param string $property
   *   The name of the property to get.
   *
   * @return null|string|array
   *   Null if empty, string if single value, array if multi value.
   */
  public function getEmailValue(EasyRdf_Resource $resource, $property) {
    $values = $this->getValueArray($resource, $property);

    $emails = [];
    foreach ($values as $value) {
      $emails[] = $this->stripMailto($value);
    }

    return $this->unifyReturnValue($emails);
  }

  /**
   * Strip mailto: at the start of the given value.
   *
   * @param string $value
   *   The value to strip mailto: from e.g. mailto:me@example.com.
   *
   * @return string
   *   The value without the mailto: part e.g. me@example.com.
   */
  public function stripMailto($value) {
    return preg_replace("/^mailto:/", '', $value);
  }

}
