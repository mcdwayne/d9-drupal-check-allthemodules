<?php

namespace Drupal\commerce_opp;

/**
 * Represents a supported brand.
 */
final class Brand {

  /**
   * The "Bank Account Brands" type.
   */
  const TYPE_BANK = 'bank';

  /**
   * The "Card Account Brands" type.
   */
  const TYPE_CARD = 'card';

  /**
   * The "Virtual Account Brands" type.
   */
  const TYPE_VIRTUAL = 'virtual';

  /**
   * The brand ID used by Open Payment Platform.
   *
   * @var string
   */
  protected $id;

  /**
   * The brand ID used by commerce_payment, if available (Credit cards only).
   *
   * @var string
   */
  protected $commerceId;

  /**
   * The type of the brand: one of "bank", "card", "virtuel".
   *
   * @var string
   */
  protected $type;

  /**
   * The brand label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether the workflow is sync/async.
   *
   * @var bool
   */
  protected $sync = FALSE;

  /**
   * Constructs a new Brand instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->type = $definition['type'];

    if (isset($definition['commerce_id'])) {
      $this->commerceId = $definition['commerce_id'];
    }
    if (isset($definition['sync'])) {
      $this->sync = $definition['sync'];
    }
  }

  /**
   * Gets the brand ID used by Open Payment Platform.
   *
   * @return string
   *   The brand ID used by Open Payment Platform.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the brand label.
   *
   * @return string
   *   The brand label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Get the brand ID used by commerce_payment, if available (credit cards).
   *
   * @return string
   *   The brand ID used by commerce_payment, if available (credit cards only).
   */
  public function getCommerceId() {
    return $this->commerceId;
  }

  /**
   * Gets the brand type.
   *
   * @return string
   *   The brand type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns whether the workflow is sync or async..
   *
   * @return bool
   *   TRUE, if the workflow is sync, FALSE otherwise.
   */
  public function isSync() {
    return $this->sync;
  }

}
