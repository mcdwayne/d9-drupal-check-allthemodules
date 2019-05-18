<?php

namespace Drupal\commerce_affirm;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Calculator;

/**
 * Currency minor units converter class.
 */
class MinorUnits implements MinorUnitsInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Constructs a new ConfigurableFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function toMinorUnits(Price $price) {
    $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');
    $currency = $currency_storage->load($price->getCurrencyCode());
    $fraction_digits = $currency->getFractionDigits();
    $number = $price->getNumber();
    if ($fraction_digits > 0) {
      $number = Calculator::multiply($number, pow(10, $fraction_digits));
    }
    return $number;
  }

}
