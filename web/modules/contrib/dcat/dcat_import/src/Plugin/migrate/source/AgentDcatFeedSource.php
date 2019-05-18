<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use EasyRdf_Resource;
use EasyRdf_Graph;

/**
 * Agent feed source.
 *
 * @MigrateSource(
 *   id = "dcat.agent"
 * )
 */
class AgentDcatFeedSource extends DcatFeedSource {

  /**
   * Not in use.
   *
   * @see getDcatData()
   */
  public function getDcatType() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDcatData(EasyRdf_Graph $graph) {
    $publishers = array();
    $datasets = $graph->allOfType('dcat:Dataset');

    /** @var EasyRdf_Resource $dataset */
    foreach ($datasets as $dataset) {
      $publishers = array_merge($publishers, $dataset->allResources('dc:publisher'));
    }

    // Remove duplicates.
    $uris = array();
    /** @var EasyRdf_Resource $publisher */
    foreach ($publishers as $key => $publisher) {
      $uri = $publisher->getUri();
      if (isset($uris[$uri])) {
        unset($publishers[$key]);
      }
      else {
        $uris[$uri] = $uri;
      }
    }

    return $publishers;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uri' => t('URI / ID'),
      'name' => t('Name'),
      'type' => t('Type'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $data = array();

    /** @var EasyRdf_Resource $agent */
    foreach ($this->getSourceData() as $agent) {
      $data[] = array(
        'uri' => $agent->getUri(),
        'name' => $this->getValue($agent, 'foaf:name'),
        'agent_type' => $this->getValue($agent, 'dc:type'),
      );
    }

    return new \ArrayIterator($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uri']['type'] = 'string';
    return $ids;
  }

}
