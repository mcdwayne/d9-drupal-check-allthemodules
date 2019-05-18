<?php

namespace Drupal\pocket;

interface PocketQueryInterface {

  /**
   * @return \Drupal\pocket\PocketItemInterface[]
   */
  public function execute(): array;

  /**
   * @param string $state
   *
   * @return $this
   */
  public function getState(string $state);

  /**
   * @param bool $favorite
   *
   * @return $this
   */
  public function getFavorites(bool $favorite = TRUE);

  /**
   * @param bool $nonFavorite
   *
   * @return $this
   */
  public function getNonFavorites(bool $nonFavorite = TRUE);

  /**
   * @param string|NULL $type
   *
   * @return $this
   */
  public function getContentType(string $type = NULL);

  /**
   * @param string|NULL $tag
   *
   * @return $this
   */
  public function getTag(string $tag = NULL);

  /**
   * @param string $order
   *
   * @return $this
   */
  public function setOrder(string $order);

  /**
   * @param bool $details
   *
   * @return $this
   */
  public function getDetails(bool $details = TRUE);

  /**
   * @param string $search
   *
   * @return $this
   */
  public function search(string $search);

  /**
   * @param string $domain
   *
   * @return $this
   */
  public function getDomain(string $domain);

  /**
   * @param int $timestamp
   *
   * @return $this
   */
  public function getSince(int $timestamp);

  /**
   * @param int $offset
   * @param int $count
   *
   * @return $this
   */
  public function getRange(int $offset, int $count);

}
