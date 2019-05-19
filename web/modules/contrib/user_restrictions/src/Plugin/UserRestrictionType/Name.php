<?php

namespace Drupal\user_restrictions\Plugin\UserRestrictionType;

/**
 * Defines a restriction type by username.
 *
 * @UserRestrictionType(
 *   id = "name",
 *   label = @Translation("Username"),
 *   weight = -10
 * )
 */
class Name extends UserRestrictionTypeBase {

  /**
   * The username.
   *
   * @var string
   */
  protected $name = '';

  /**
   * {@inheritdoc}
   */
  public function matches(array $data) {
    if (!isset($data['name'])) {
      return FALSE;
    }
    $this->name = $data['name'];
    $restriction = parent::matchesValue($this->name);
    if ($restriction) {
      $this->logger->notice('Restricted name %name matching %restriction has been blocked.', ['%name' => $this->name, '%restriction' => $restriction->toLink($restriction->label(), 'edit-form')]);
    }
    return $restriction;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->t('The name %name is reserved, and cannot be used.', ['%name' => $this->name]);
  }

}
