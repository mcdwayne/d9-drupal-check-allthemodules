<?php

namespace Drupal\pocket;

use Drupal\pocket\Client\PocketUserClientInterface;

class PocketQuery implements PocketQueryInterface {

  const STATE_ALL = 'all';

  const STATE_ARCHIVED = 'archived';

  const STATE_UNREAD = 'unread';

  const ORDER_NEWEST = 'newest';

  const ORDER_OLDEST = 'oldest';

  const ORDER_SITE = 'site';

  const ORDER_TITLE = 'title';

  const TYPE_ARTICLE = 'article';

  const TYPE_IMAGE = 'image';

  const TYPE_VIDEO = 'video';

  /**
   * @var \Drupal\pocket\Client\PocketUserClientInterface
   */
  protected $client;

  /**
   * @var bool
   */
  protected $favorites;

  /**
   * @var bool
   */
  protected $nonFavorites;

  /**
   * @var array
   */
  protected $values;

  /**
   * PocketQuery constructor.
   *
   * @param \Drupal\pocket\Client\PocketUserClientInterface $client
   * @param array                                           $values
   */
  public function __construct(PocketUserClientInterface $client, array $values = []) {
    $this->client = $client;
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): array {
    return $this->client->retrieve($this->buildQuery());
  }

  /**
   * {@inheritdoc}
   */
  public function getState(string $state) {
    $this->values['state'] = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnread(bool $unread = TRUE) {
    $this->unread = $unread;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFavorites(bool $favorite = TRUE) {
    $this->favorites = $favorite;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNonFavorites(bool $nonFavorite = TRUE) {
    $this->nonFavorites = $nonFavorite;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(string $type = NULL) {
    $this->values['contentType'] = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTag(string $tag = NULL) {
    if ($tag !== NULL) {
      $this->values['tag'] = $tag ?: '_untagged_';
    }
    else {
      unset($this->values['tag']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder(string $order) {
    $this->values['sort'] = $order;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(bool $details = TRUE) {
    $this->values['detailType'] = $details ? 'complete' : 'simple';
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function search(string $search) {
    $this->values['search'] = $search;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomain(string $domain) {
    $this->values['domain'] = $domain;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSince(int $timestamp) {
    $this->values['since'] = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRange(int $offset, int $count) {
    $this->values['offset'] = $offset;
    $this->values['count'] = $count;
    return $this;
  }

  /**
   * @return array
   */
  protected function buildQuery(): array {
    if ($this->favorites !== $this->nonFavorites) {
      $this->values['favorite'] = (int) $this->favorites;
    }
    else {
      unset($this->values['favorite']);
    }
    return $this->values;
  }

}
