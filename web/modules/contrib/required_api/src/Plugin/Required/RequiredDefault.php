<?php

namespace Drupal\required_api\Plugin\Required;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 *
 * @Required(
 *   id = "default",
 *   label = @Translation("Core"),
 *   description = @Translation("Required based on core implementation.")
 * )
 */
class RequiredDefault extends RequiredBase {

  /**
   * Determines whether or not a field is required.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   A field instance object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account object.
   *
   * @return bool
   *   TRUE on required. FALSE otherwise.
   */
  public function isRequired(FieldDefinitionInterface $field, AccountInterface $account) {
    return $field->isRequired();
  }

  /**
   * Determines wether a field is required or not.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   A field instance object.
   *
   * @return bool
   *   TRUE on required. FALSE otherwise.
   */
  public function requiredFormElement(FieldDefinitionInterface $field) {

    $element = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $field->isRequired(),
      '#weight' => -5,
    );

    return $element;
  }

}
