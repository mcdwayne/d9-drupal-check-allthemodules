<?php

namespace Drupal\taxonomy_facets;

class GetNodes {

  private $filters;
  private $nodeTypes = null;
  private $start;
  private $limit;
  private $number_of_nodes;

  public function __construct($filters) {

    $this->filters = $filters;

    $config = \Drupal::config('taxonomy_facets.settings');
    $this->limit = $config->get('number_of_nodes_per_page');

    // Set node type that are part of filtering, from user config.
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach($contentTypes as $contentType){
      if($config->get('ct_' . $contentType->id())){
        $this->nodeTypes[] = $contentType->id();
      }
    }

    if (empty($_REQUEST['page'])) {
      $this->start = 0;
    }
    else {
      $this->start = $_REQUEST['page'] * $this->limit;
    }
  }

  public function getNodes() {

    $query = db_select('node', 'n')
      ->fields('n', array('nid'));

    $cnt = 0;
    foreach ($this->filters as $key => $value) {
      $query->innerJoin('taxonomy_index', 'ti' . $cnt, 'n.nid = ti' . $cnt . ' .nid ');
      $query->condition('ti' . $cnt . '.tid', $value);
      $cnt++;
    }
    if($this->nodeTypes){
      $query->condition('n.type', $this->nodeTypes, 'IN');
    }

    // Count all nodes first.
    $number_of_nodes = $query->execute();
    $number_of_nodes->allowRowCount = TRUE;
    $this->number_of_nodes = $number_of_nodes->rowCount();

    // Add range, for use in pager
    $query->range($this->start, $this->limit);
    $nodes = $query->execute()->fetchAll();
    $return = array();
    foreach ($nodes as $node) {
      $return[] = $node->nid;
    }
    return $return;
  }


  public function getNumberOfNodes(){
    return $this->number_of_nodes;
  }
}