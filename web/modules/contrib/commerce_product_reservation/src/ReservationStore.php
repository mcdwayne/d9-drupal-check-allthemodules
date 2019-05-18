<?php

namespace Drupal\commerce_product_reservation;

/**
 * Class ReservationStore.
 *
 * A stupid value object.
 */
class ReservationStore {

  /**
   * The id.
   *
   * @var string
   */
  protected $id;

  /**
   * The name.
   *
   * @var string
   */
  protected $name;

  /**
   * Provider.
   *
   * @var string
   */
  protected $provider;

  /**
   * Getter.
   *
   * @return string
   *   Provider.
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Provider.
   *
   * @param string $provider
   *   Provider.
   */
  public function setProvider($provider) {
    $this->provider = $provider;
  }

  /**
   * Getter.
   *
   * @return string
   *   ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Setter.
   *
   * @param string $id
   *   ID.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Getter.
   *
   * @return string
   *   Name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Setter.
   *
   * @param string $name
   *   Name.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Helper.
   */
  public static function createFromValues(array $values) {
    $store = new static();
    if (isset($values['name'])) {
      $store->setName($values['name']);
    }
    if (isset($values['id'])) {
      $store->setId($values['id']);
    }
    if (isset($values['provider'])) {
      $store->setProvider($values['provider']);
    }
    return $store;
  }

}
