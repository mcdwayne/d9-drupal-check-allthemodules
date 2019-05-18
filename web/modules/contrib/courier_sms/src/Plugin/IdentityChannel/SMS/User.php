<?php

namespace Drupal\courier_sms\Plugin\IdentityChannel\SMS;


use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\Exception\IdentityException;
use Drupal\sms\Exception\PhoneNumberSettingsException;

/**
 * Supports core user entities.
 *
 * @IdentityChannel(
 *   id = "identity:user:sms",
 *   label = @Translation("Drupal user to SMS"),
 *   channel = "courier_sms",
 *   identity = "user",
 *   weight = 10
 * )
 */
class User implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\courier_sms\Entity\SmsMessage $message
   * @param \Drupal\user\UserInterface $identity
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {
    /** @var \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider */
    $phone_number_provider = \Drupal::service('sms.phone_number');

    try {
      $phone_numbers = $phone_number_provider->getPhoneNumbers($identity);
      if ($phone_number = reset($phone_numbers)) {
        $message->setRecipient($phone_number);
      }
      else {
        throw new IdentityException('User does not have any confirmed phone numbers.');
      }
    }
    catch (PhoneNumberSettingsException $e) {
      throw new IdentityException('Users are not configured for phone numbers.');
    }
  }

}
