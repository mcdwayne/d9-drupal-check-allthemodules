<?php

namespace Drupal\pocket\Action;

class ModifyAction extends PocketAction {

  /**
   * @var string
   */
  private $action;

  /**
   * ModifyAction constructor.
   *
   * @param string $action
   * @param int    $id
   * @param array  $values
   */
  public function __construct(string $action, int $id, array $values = []) {
    parent::__construct($values);
    $this->action = $action;
    $this->setId($id);
  }

  /**
   * @param int   $id
   * @param array $values
   *
   * @return static
   */
  public static function archive(int $id, array $values = []) {
    return new static('archive', $id, $values);
  }

  /**
   * @param int   $id
   * @param array $values
   *
   * @return static
   */
  public static function readd(int $id, array $values = []) {
    return new static('readd', $id, $values);
  }

  /**
   * @param int   $id
   * @param array $values
   *
   * @return static
   */
  public static function favorite(int $id, array $values = []) {
    return new static('favorite', $id, $values);
  }

  /**
   * @param int   $id
   * @param array $values
   *
   * @return static
   */
  public static function unfavorite(int $id, array $values = []) {
    return new static('unfavorite', $id, $values);
  }

  /**
   * @param int   $id
   * @param array $values
   *
   * @return static
   */
  public static function delete(int $id, array $values = []) {
    return new static('delete', $id, $values);
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
   * @return string
   */
  protected function getName(): string {
    return $this->action;
  }

}
