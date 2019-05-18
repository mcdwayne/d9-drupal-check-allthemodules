<?php

namespace Drupal\courier_ui\Plugin\IdentityChannel\CourierTranslatableEmail;

use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Supports core user entities.
 *
 * @IdentityChannel(
 *   id = "identity:user:courier_translatable_email",
 *   label = @Translation("Drupal user to courier_translatable_mail"),
 *   channel = "courier_translatable_email",
 *   identity = "user",
 *   weight = 10
 * )
 */
class User implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {
    /** @var \Drupal\user\UserInterface $identity */
    /** @var \Drupal\courier_ui\TranslatableEmailInterface $message */
    $message->setRecipientName($identity->label());
    $message->setEmailAddress($identity->getEmail());

    $langcode = $identity->getPreferredLangcode();
    $message->setReceipientLanguage($langcode);
  }

}
