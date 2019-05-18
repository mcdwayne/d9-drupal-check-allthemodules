<?php

namespace Drupal\pocket\Action;

class TagsAction extends PocketAction {

  /**
   * @var string
   */
  private $action;

  /**
   * TagsAction constructor.
   *
   * @param string $action
   * @param int    $id
   * @param array  $tags
   * @param array  $values
   */
  public function __construct(string $action, int $id, array $tags = [], array $values = []) {
    parent::__construct($values);
    $this->setTags($tags)->setId($id);
    $this->action = $action;
  }

  /**
   * @param string $id
   * @param array  $tags
   * @param array  $options
   *
   * @return static
   */
  public static function add(string $id, array $tags, array $options) {
    return new static('tags_add', $id, $tags, $options);
  }

  /**
   * @param string $id
   * @param array  $tags
   * @param array  $options
   *
   * @return static
   */
  public static function remove(string $id, array $tags, array $options) {
    return new static('tags_remove', $id, $tags, $options);
  }

  /**
   * @param string $id
   * @param array  $tags
   * @param array  $options
   *
   * @return static
   */
  public static function replace(string $id, array $tags, array $options) {
    return new static('tags_replace', $id, $tags, $options);
  }

  /**
   * @param string $id
   * @param array  $options
   *
   * @return mixed
   */
  public static function clear(string $id, array $options) {
    return (new static('tags_clear', $id, [], $options))
      ->unset('tags');
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
   * @param array $tags
   *
   * @return $this
   */
  public function setTags(array $tags = []) {
    return $this->set('tags', implode(', ', $tags));
  }

  /**
   * @return string
   */
  protected function getName(): string {
    return $this->action;
  }

}
