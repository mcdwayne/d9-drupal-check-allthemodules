<?php

namespace Drupal\kits;

use Drupal\kits\Services\KitsInterface;

/**
 * Class Kit
 *
 * @package Drupal\formfactorykits\Kits
 */
abstract class Kit implements KitInterface {
  const ID_KEY = 'id';
  const ID = NULL;
  const GROUP_KEY = 'group';

  /**
   * @var \Drupal\kits\Services\KitsInterface
   */
  protected $kitsService;

  /**
   * @var array
   */
  protected $parameters;

  /**
   * @var Kit[]
   *   Any child kits associated with this kit.
   */
  protected $kits = [];

  /**
   * @var array
   */
  protected $keys;

  /**
   * @var array
   */
  protected $excludedParameters = [];

  /**
   * @var array
   */
  protected $context;

  /**
   * Kit constructor.
   *
   * @param KitsInterface $kitsService
   * @param string|null $id
   * @param array $parameters
   * @param array $context
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    $this->kitsService = $kitsService;
    if (NULL !== $id) {
      $context[self::ID_KEY] = $id;
    }
    if (!array_key_exists(self::ID_KEY, $context) && NULL !== static::ID) {
      $context[self::ID_KEY] = static::ID;
    }
    $this->parameters = $parameters;
    $this->context = $context;
  }

  /**
   * @inheritdoc
   */
  public static function create(KitsInterface $kitsService, $id = NULL, array $parameters = [], array $context = []) {
    return new static($kitsService, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function append(KitInterface $kit) {
    $this->kits[] = $kit;
    return $this;
  }

  /**
   * @param string $parentID
   * @return static
   */
  public function appendParent($parentID) {
    $this->context['parents'][] = $parentID;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function excludeParameter($parameter) {
    $this->excludedParameters[] = $parameter;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function get($key, $default = NULL) {
    if (array_key_exists($key, $this->parameters)) {
      return $this->parameters[$key];
    }
    else {
      return $default;
    }
  }

  /**
   * @inheritdoc
   */
  public function getChildrenArray() {
    $artifact = [];
    foreach ($this->kits as $kit) {
      /** @var Kit $kit */
      $id = $this->getID();
      $kit->appendParent($id);
      $kit->setGroup($id);
      $artifact[$kit->getID()] = $kit->getArray();
    }
    return $artifact;
  }

  /**
   * @inheritdoc
   */
  public function getContext($key, $default = NULL) {
    if (array_key_exists($key, $this->context)) {
      return $this->context[$key];
    }
    else {
      return $default;
    }
  }

  /**
   * @inheritdoc
   */
  public function getID() {
    $id = $this->getContext(self::ID_KEY);
    if (empty($id)) {
      throw new \LogicException(vsprintf('Could not find kit ID for %s', [
        static::class,
      ]));
    }
    return $id;
  }

  /**
   * @inheritdoc
   */
  public function getParents() {
    if (array_key_exists('parents', $this->context)) {
      return $this->context['parents'];
    }
    else {
      return [];
    }
  }

  /**
   * @inheritdoc
   */
  public function has($key) {
    return array_key_exists($key, $this->parameters);
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value) {
    $this->parameters[$key] = $value;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function setContext($key, $value) {
    $this->context[$key] = $value;
    return $this;
  }

  /**
   * @inheritdoc
   */
  public function setID($value) {
    return $this->setContext(self::ID_KEY, $value);
  }

  /**
   * @inheritdoc
   */
  public function setGroup($group) {
    return $this->set(self::GROUP_KEY, $group);
  }
}
