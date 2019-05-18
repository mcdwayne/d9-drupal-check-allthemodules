<?php

namespace Drupal\entity_pager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Interface EntityPagerInterface.
 */
interface EntityPagerInterface {

  /**
   * Gets the view for the entity pager.
   *
   * @return ViewExecutable
   *   The view object.
   */
  public function getView();

  /**
   * Gets an array of entity pager links.
   *
   * @return array
   */
  public function getLinks();

  /**
   * Get result count word.
   *
   * Get the word associated with the count of results.
   * i.e. one, many
   * The number in the result converted to a summary word for privacy.
   *
   * @return string
   *   Get a text representation the number of records e.g. none, one or many.
   */
  public function getCountWord();

  /**
   * Gets the entity object this entity pager is for.
   *
   * @return EntityInterface|NULL
   *   The entity object or NULL if no entity found.
   */
  public function getEntity();

  /**
   * Returns the options this entity pager was created with.
   *
   * @return array
   */
  public function getOptions();
};
