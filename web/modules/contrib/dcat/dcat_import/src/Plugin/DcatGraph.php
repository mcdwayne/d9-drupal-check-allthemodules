<?php

namespace Drupal\dcat_import\Plugin;

use EasyRdf_Graph;
use EasyRdf_Resource;
use EasyRdf_Http_Exception;

/**
 * Class DcatGraph.
 *
 * @package Drupal\dcat_import\Plugin
 */
class DcatGraph extends EasyRdf_Graph {

  /**
   * Pager argument.
   *
   * @var string.
   */
  public $pagerArgument = 'page';


  /**
   * {@inheritdoc}
   */
  public function __construct($uri = NULL, $data = NULL, $format = NULL) {
    parent::__construct($uri, $data, $format);

    \EasyRdf_Namespace::set('adms', 'http://www.w3.org/ns/adms#');
  }

  /**
   * {@inheritdoc}
   */
  public static function newAndLoad($uri, $format = NULL, $pager_argument = NULL) {
    $graph = new self($uri);
    if (!empty($pager_argument)) {
      $graph->pagerArgument = $pager_argument;
    }
    $graph->load($uri, $format);

    return $graph;
  }

  /**
   * Returns the pager argument to use in the uri.
   *
   * @return string
   *   The pager argument
   */
  public function getPagerArgument() {
    return $this->pagerArgument;
  }

  /**
   * Compare two result sets.
   *
   * @param array $previous
   *   An array of EasyRdf_Resource objects.
   * @param array $current
   *   An array of EasyRdf_Resource objects to compare to.
   *
   * @return bool
   *   True if results are the same.
   */
  public function compareResults(array $previous, array $current) {
    // To limit complexity and execution time, only compare the last result.
    /** @var EasyRdf_Resource $previous_last */
    $previous_last = end($previous);
    /** @var EasyRdf_Resource $current_last */
    $current_last = end($current);

    return $previous_last->getUri() == $current_last->getUri();
  }

  /**
   * Returns all none blank resources.
   *
   * @param array $resources
   *   List of resources to look into, defaults to $this->resources().
   *
   * @return array
   *   Array of none blank resources;
   */
  public function getNoneBlankResources($resources = array()) {
    $resources = empty($resources) ? $this->resources() : $resources;

    /** @var EasyRdf_Resource $resource */
    foreach ($resources as $key => $resource) {
      if ($resource->isBNode() || empty($resource->type())) {
        unset($resources[$key]);
      }
    }

    return $resources;
  }

  /**
   * Returns paged url.
   *
   * @param string $base
   *   The base url.
   * @param string $argument
   *   The pager argument.
   * @param int $count
   *   The page number.
   *
   * @return string
   *   The paged url.
   */
  public function pagedUrlBuilder($base, $argument, $count) {
    // If no $base is given, return NULL.
    if (empty($base)) {
      return NULL;
    }

    // Some servers return a 404 if we try to use arguments, so ignoring this
    // for page 1.
    if ($count > 1) {
      $separator = strpos($base, '?') ? '&' : '?';
      return $base . $separator . $argument . '=' . $count;
    }
    else {
      return $base;
    }
  }

  /**
   * Load a single RDF page into the graph from a URI.
   *
   * If no URI is given, then the URI of the graph will be used.
   *
   * The document type is optional but should be specified if it
   * can't be guessed or got from the HTTP headers.
   *
   * @param string $uri
   *   The URI of the data to load.
   * @param string $format
   *   Optional format of the data (eg. rdfxml).
   *
   * @throws \Exception
   *   When there is no data.
   *
   * @return int
   *   The number of triples added to the graph.
   */
  public function loadSingle($uri = NULL, $format = NULL) {
    return parent::load($uri, $format);
  }

  /**
   * Load RDF data into the graph from a URI.
   *
   * Overridden to support 'Data Catalog Interoperability Protocol', as this
   * describes that the data can be spun out over different pages with a pager
   * argument.
   *
   * @param string $uri
   *   The URI of the data to load.
   * @param string $format
   *   Optional format of the data (eg. rdfxml).
   *
   * @throws \Exception
   *   When there is no data.
   *
   * @return int
   *   The number of triples added to the graph.
   */
  public function load($uri = NULL, $format = NULL) {
    $page = 1;

    while (TRUE) {
      $current_uri = $this->pagedUrlBuilder($uri, $this->getPagerArgument(), $page);

      // Because of the 'Data Catalog Interoperability Protocol' we need to
      // test on a couple of different scenarios.
      // @see http://spec.dataportals.org/#extra-parameters
      try {
        $this->loadSingle($current_uri, $format);
        $current_data = $this->getNoneBlankResources();

        if (empty($current_data)) {
          // No data (left).
          break;
        }
        if (isset($previous_data) && $this->compareResults($previous_data, $current_data)) {
          // Result is the same as the previous. This happens if source does not
          // support the extra parameters.
          break;
        }
      }
      catch (EasyRdf_Http_Exception $e) {
        if ($e->getCode() == 404 && !empty($current_data)) {
          // When we receive a 404 after we have already received data, it is
          // most likely that we just encountered the end of the DCAT feed.
          break;
        }
        throw $e;
      }

      $page++;
      $previous_data = $current_data;
    }

    return count($this->getNoneBlankResources());
  }

}
