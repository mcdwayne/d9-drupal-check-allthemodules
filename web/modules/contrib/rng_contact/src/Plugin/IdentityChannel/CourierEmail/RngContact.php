<?php

namespace Drupal\rng_contact\Plugin\IdentityChannel\CourierEmail;

use Drupal\courier\Exception\IdentityException;
use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rng_contact\Entity\RngContactType;

/**
 * Supports rng_contact entities.
 *
 * @IdentityChannel(
 *   id = "identity:rng_contact:courier_email",
 *   label = @Translation("rng_contact to courier_mail"),
 *   channel = "courier_email",
 *   identity = "rng_contact",
 *   weight = 10
 * )
 */
class RngContact implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {
    /** @var \Drupal\rng_contact\Entity\RngContactInterface $identity */
    /** @var \Drupal\courier\EmailInterface $message */
    $contact_type = RngContactType::load($identity->bundle());
    $email_field = $contact_type->getCourierEmailField();
    if ($email_field && isset($identity->{$email_field})) {
      $email = $identity->{$email_field};
      if (!empty($email->value)) {
        $message->setRecipientName($identity->label());
        $message->setEmailAddress($email->value);
      }
      else {
        throw new IdentityException('Contact missing email address.');
      }
    }
    else {
      throw new IdentityException('Contact type email field not configured.');
    }
  }

}
