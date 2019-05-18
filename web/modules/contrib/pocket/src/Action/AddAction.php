<?php

namespace Drupal\pocket\Action;

class AddAction extends PocketAction {

  const ACTION = 'add';

  /**
   * AddAction constructor.
   *
   * @param string $urlOrId
   * @param array  $options
   */
  public function __construct(string $urlOrId, array $options = []) {
    parent::__construct($options);
    $this->set(is_numeric($urlOrId) ? 'item_id' : 'url', $urlOrId);
  }

  /**
   * @param string $id
   *
   * @return $this
   */
  public function setId(string $id) {
    return $this->set('item_id', $id);
  }

  /**
   * @param string $url
   *
   * @return $this
   */
  public function setUrl(string $url) {
    return $this->set('url', $url);
  }

  /**
   * @param string[] $tags
   *
   * @return $this
   */
  public function setTags(array $tags) {
    return $this->set('tags', implode(', ', $tags));
  }

  /**
   * @param string $title
   *
   * @return $this
   */
  public function setTitle(string $title) {
    return $this->set('title', $title);
  }

}
