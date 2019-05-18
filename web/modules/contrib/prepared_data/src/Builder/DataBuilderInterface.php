<?php

namespace Drupal\prepared_data\Builder;
use Drupal\prepared_data\PreparedDataInterface;

/**
 * Interface for classes which build up and refresh prepared data.
 */
interface DataBuilderInterface {

  /**
   * Builds up prepared data for the given key.
   *
   * @param string $key
   *   The key which identifies the prepared data.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface
   *   A newly build data-set.
   */
  public function build($key);

  /**
   * Refreshes the given prepared data.
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data to refresh.
   */
  public function refresh(PreparedDataInterface $data);

}
