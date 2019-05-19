<?php

namespace Drupal\user_restrictions\Plugin\UserRestrictionType;

/**
 * Defines a restriction type by email.
 *
 * @UserRestrictionType(
 *   id = "mail",
 *   label = @Translation("Email"),
 *   weight = 0
 * )
 */
class Email extends UserRestrictionTypeBase {

  /**
   * The users email address.
   *
   * @var string
   */
  protected $mail = '';

  /**
   * {@inheritdoc}
   */
  public function matches(array $data) {
    if (!isset($data['mail'])) {
      return FALSE;
    }
    $this->mail = $data['mail'];
    $restriction = parent::matchesValue($this->mail);
    if ($restriction) {
      $this->logger->notice('Restricted email %mail matching %restriction has been blocked.', ['%mail' => $this->mail, '%restriction' => $restriction->toLink($restriction->label(), 'edit-form')]);
    }
    return $restriction;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->t('The email %mail is reserved, and cannot be used.', ['%mail' => $this->mail]);
  }

}
