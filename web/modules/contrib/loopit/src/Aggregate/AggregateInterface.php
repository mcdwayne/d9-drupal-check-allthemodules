<?php
namespace Drupal\loopit\Aggregate;

/**
 * @todo comments
 */
interface AggregateInterface extends \IteratorAggregate, \ArrayAccess {

  public function getOptions();
  public function getContext();
  public function getParent();
  public function getInput();
  public function getCacheNested();
  public function getArrayParents();
  public function getDepth();

  public function preDown($key);
  public function onDown($aggregate);
  public function preUp();
  public function onCurrent($current, $index);
  public function onLeaf($current, $index);

}