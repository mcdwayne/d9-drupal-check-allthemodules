<?php

namespace Drupal\wrappers_delight;

use Drupal\Core\Entity\ContentEntityInterface;

abstract class WrapperBase {

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * WrapperBase constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function __construct(ContentEntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * @return string
   */
  abstract public static function entity_type();

  /**
   * @return string
   */
  abstract public static function bundle();

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function raw() {
    return $this->entity;
  }

  /**
   * @return int
   */
  public function save() {
    return $this->entity->save();
  }

  /**
   * @param array $values
   *
   * @return static
   */
  public static function create(array $values = []) {
    if (!is_null(static::bundle())) {
      $bundle_key = \Drupal::entityTypeManager()->getStorage(static::entity_type())->getEntityType()->getKey('bundle');
      $values += [
        $bundle_key => static::bundle(),
      ];
    }
    return static::wrap(\Drupal::entityTypeManager()->getStorage(static::entity_type())->create($values));
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|int $entity
   *
   * @return static
   */
  public static function wrap($entity) {
    if (!($entity instanceof ContentEntityInterface)) {
      $entity = \Drupal::entityTypeManager()->getStorage(static::entity_type())->load($entity);
    }
    return new static($entity);
  }

  /**
   * @param int $id
   *
   * @return static
   */
  public static function load($id) {
    return static::wrap(\Drupal::entityTypeManager()->getStorage(static::entity_type())->load($id));
  }

  /**
   * @param array|NULL $ids
   *
   * @return static[]
   */
  public static function loadMultiple(array $ids = NULL) {
    $wrapped = [];
    foreach (\Drupal::entityTypeManager()->getStorage(static::entity_type())->loadMultiple($ids) as $entity) {
      $wrapped[] = static::wrap($entity);
    }
    return $wrapped;
  }

  /**
   * @param string $field_name
   *
   * @return \Drupal\wrappers_delight\FieldItemWrapper
   *
   * @throws \InvalidArgumentException
   */
  protected function getFieldFirst($field_name) {
    if ($this->entity->hasField($field_name)) {
      $list = $this->entity->get($field_name);
      if (!$list->isEmpty()) {
        return \Drupal::service('plugin.manager.wrappers_delight')->wrapField($this->entity->get($field_name)->first());
      }
      else {
       return new EmptyFieldItemWrapper();
      }
    }
    throw new \InvalidArgumentException(t("Field @field_name does not exist.", ['@field_name' => $field_name]));
  }

  /**
   * @param string $field_name
   *
   * @return \Drupal\wrappers_delight\FieldItemListWrapper
   * 
   * @throws \InvalidArgumentException
   */
  public function getField($field_name) {
    if ($this->entity->hasField($field_name)) {
      return \Drupal::service('plugin.manager.wrappers_delight')->wrapFieldList($this->entity->get($field_name));
    }
    throw new \InvalidArgumentException(t("Field @field_name does not exist.", ['@field_name' => $field_name]));
  }

  /**
   * @param string $field_name
   * @param mixed $value
   * @param bool $notify
   *
   * @return $this
   */
  public function set($field_name, $value, $notify = TRUE) {
    if ($value instanceof FieldItemWrapper || $value instanceof WrapperBase) {
      $value = $value->raw();
    }
    $this->entity->set($field_name, $value, $notify);
    return $this;
  }

  /**
   * @return int|null|string
   */
  public function id() {
    return $this->entity->id();
  }

  /**
   * @inheritDoc
   */
  function __call($name, $arguments) {
    return call_user_func_array([$this->entity, $name], $arguments);
  }

}
