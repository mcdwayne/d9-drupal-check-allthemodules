<?php

namespace Drupal\dcat_export\Event;

use Symfony\Component\EventDispatcher\Event;
use EasyRdf_Graph;

/**
 * Provides an serialize-graph event for event listeners.
 */
class SerializeGraphEvent extends Event {

  /**
   * EasyRdf graph object.
   *
   * @var \EasyRdf_Graph
   */
  protected $graph;

  /**
   * Constructs a configuration event object.
   *
   * @param \EasyRdf_Graph $graph
   *   The EasyRdf resource, based on the given entity.
   */
  public function __construct(EasyRdf_Graph $graph) {
    $this->graph = $graph;
  }

  /**
   * Gets the graph object.
   *
   * Alter this graph object right before it gets serialized to an RDF format.
   * Note that the object is a reference. There is no need to set the object
   * after altering.
   *
   * @return \EasyRdf_Graph
   *   The EasyRdf graph.
   */
  public function getGraph() {
    return $this->graph;
  }

}
