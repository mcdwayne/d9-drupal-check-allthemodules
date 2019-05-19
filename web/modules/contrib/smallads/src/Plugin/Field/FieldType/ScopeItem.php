<?php

namespace Drupal\smallads\Plugin\Field\FieldType;

use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the 'scope' smallad field type.
 *
 * @FieldType(
 *   id = "smallad_scope",
 *   label = @Translation("Smallad Scope"),
 *   description = @Translation("How widely this ad can be seen"),
 *   category = @Translation("Smallads"),
 *   default_widget = "smallad_scope"
 * )
 */


class ScopeItem extends IntegerItem implements OptionsProviderInterface{

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys(smallads_scopes());
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return smallads_scopes();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return $this->getPossibleOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return rand(0, 4);
  }
}
