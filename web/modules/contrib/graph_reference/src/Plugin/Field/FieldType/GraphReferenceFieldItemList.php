<?php

namespace Drupal\graph_reference\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\graph_reference\Entity\Graph;

/**
 * Class GraphReferenceItem
 * @package Plugin\Field\FieldType
 */
class GraphReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * @var \Drupal\graph_reference\Entity\GraphInterface
   */
  protected $graph;

  /**
   * Indicates if the graph reference field item list has been initialized.
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    parent::__clone();
    $this->initialized = FALSE;
  }

  /**
   * This method initializes the graph entity and all the edge targets.
   *
   * @param bool $force
   *   Set this parameter to TRUE to force the initialization of existing values.
   */
  protected function initialize($force = FALSE) {
    if (!$this->initialized || $force) {
      $this->initialized = TRUE;
      $this->graph = Graph::load($this->getSetting('graph_id'));
      $this->setValue($this->graph->getEdgesOf($this->getEntity()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->initialize();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    $this->initialize();
    return parent::__get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $this->initialize();
    return parent::getString();
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    $this->initialize();
    return parent::get($index);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->initialize();
    return parent::getIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $this->initialize();
    return parent::count();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->initialize();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function filter($callback) {
    $this->initialize();
    return parent::filter($callback);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    /** @var \Drupal\graph_reference\Entity\GraphInterface $graph */
    $graph = Graph::load($this->getSetting('graph_id'));

    $graph->setEdgesOf($this->getEntity(), $this->referencedEntities());

    return parent::postSave($update);
  }


  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface[] $former_edges */
    $former_edges = [];
    foreach ($this->list as $item) {
      if (isset($item->entity)) {
        $former_edges[$item->entity->uuid()] = $item->entity;
      }
    }

    $this->doSetValue($values, $notify);
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    foreach ($this->list as $item) {
      if (isset($item->entity)) {
        $this->setReverseReference($item->entity);
        unset($former_edges[$item->entity->uuid()]);
      }
    }

    foreach ($former_edges as $former_edge) {
      $this->removeReverseReference($former_edge);
    }
  }

  /**
   * Wraps the call to the original setValue method in a protected method because
   * the overridden one performs actions that could cause recursion.
   *
   * @param $values
   * @param bool $notify
   *
   * @see EntityReferenceFieldItemList::setValue
   */
  protected function doSetValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function set($index, $value) {
    $this->initialize();
    parent::set($index, $value);
    $index = isset($this->list[$index]) ? $index : ($this->count() - 1);
    $this->setReverseReference($this->list[$index]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($index) {
    $this->initialize();
    if (isset($this->list) && array_key_exists($index, $this->list)) {
      $this->removeReverseReference($this->list[$index]->entity);
    }
    return parent::removeItem($index);
  }

  /**
   * {@inheritdoc}
   */
  public function appendItem($value = NULL) {
    $this->initialize();
    $item = parent::appendItem($value);
    if (isset($item->entity)) {
      $this->setReverseReference($item->entity);
    }
    return $item;
  }

  /**
   * Adds the entity that holds this list as a reference value of the received
   * edge target.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $edge
   */
  protected function setReverseReference(FieldableEntityInterface $edge) {
    /** @var \Drupal\graph_reference\Plugin\Field\FieldType\GraphReferenceFieldItemList $edge_edges_list */
    $edge_edges_list = $edge->get($this->getName());

    $edge_edges = [];
    foreach ($edge_edges_list as $edge_edge) {
      if (isset($edge_edge->entity)) {
        $edge_edges[$edge_edge->entity->uuid()] = $edge_edge->getValue();
      }
      else {
        $edge_edges[$edge_edge->target_id] = $edge_edge->getValue();
      }
    }

    $key = $this->getEntity()->uuid();
    if (!$this->getEntity()->isNew() && isset($edge_edges[$this->getEntity()->id()])) {
      $key = $this->getEntity()->id();
    }

    $edge_edges[$key] = ['entity' => $this->getEntity()];

    $edge_edges_list->doSetValue(array_values($edge_edges));
  }

  /**
   * Removes the entity that holds this list from the references of the received
   * edge target.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $edge
   */
  protected function removeReverseReference(FieldableEntityInterface $edge) {
    /** @var \Drupal\graph_reference\Plugin\Field\FieldType\GraphReferenceFieldItemList $edge_edges_list */
    $edge_edges_list = $edge->get($this->getName());

    $edge_edges = [];
    foreach ($edge_edges_list as $edge_edge) {
      $edge_edges[$edge_edge->entity->uuid()] = $edge_edge->getValue();
    }
    unset($edge_edges[$this->getEntity()->uuid()]);

    $edge_edges_list->doSetValue(array_values($edge_edges));
  }

}