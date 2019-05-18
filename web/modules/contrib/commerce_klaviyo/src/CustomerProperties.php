<?php

namespace Drupal\commerce_klaviyo;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * The Klaviyo Customer Properties object.
 *
 * @package Drupal\commerce_klaviyo
 */
class CustomerProperties extends KlaviyoPropertiesBase {

  /**
   * Constructs the CustomerProperties based on the User entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User account.
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *   The user profile. If empty - the script will try to get the default
   *   user's profile.
   *
   * @return \Drupal\commerce_klaviyo\CustomerProperties
   *   The CustomerProperties.
   */
  public static function createFromUser(AccountInterface $user, ProfileInterface $profile = NULL) {
    $obj = new static(\Drupal::service('config.factory'), $user);
    $obj->properties['$email'] = $user->getEmail();
    $obj->properties['$timezone'] = $user->getTimeZone();
    $obj->setUserRoles($user);

    if (!$profile) {
      /** @var \Drupal\profile\Entity\ProfileInterface|bool $active_profile */
      $profile = \Drupal::service('entity_type.manager')
        ->getStorage('profile')
        ->loadDefaultByUser($user, 'customer') ?: NULL;
    }

    if ($profile) {
      $obj->setCustomerInfo($profile);
    }

    return $obj;
  }

  /**
   * Constructs the CustomerProperties based on the Order entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Order entity.
   *
   * @return \Drupal\commerce_klaviyo\CustomerProperties
   *   The CustomerProperties.
   */
  public static function createFromOrder(OrderInterface $order) {
    $obj = new static(\Drupal::service('config.factory'), $order);
    $obj->properties['$email'] = $order->getEmail();

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    if ($billing_profile = $order->getBillingProfile()) {
      $obj->setCustomerInfo($billing_profile);
    }
    return $obj;
  }

  /**
   * Adds user roles to properties.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   */
  protected function setUserRoles(AccountInterface $user) {
    $this->properties['UserRoles'] = $user->getRoles();
  }

  /**
   * Sets the customer info based on the provided user profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The user profile entity.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   See \Drupal\Core\TypedData\ListInterface::first().
   */
  protected function setCustomerInfo(ProfileInterface $profile) {
    if (($address_field = $profile->get('address')) && !$address_field->isEmpty()) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
      $address = $address_field->first();

      $this->properties['$first_name'] = $address->getGivenName();
      $this->properties['$last_name'] = $address->getFamilyName();
      $this->properties['$organization'] = $address->getOrganization();
      $this->properties['$city'] = $address->getLocality();
      $this->properties['$region'] = $address->getAdministrativeArea();
      $this->properties['$zip'] = $address->getPostalCode();
      $this->properties['$country'] = $address->getCountryCode();
      $this->properties['address1'] = $address->getAddressLine1();
      $this->properties['address2'] = $address->getAddressLine2();
    }

    $telephone_field = $this->configFactory
      ->get('commerce_klaviyo.settings')
      ->get('telephone_field');
    $phone = $telephone_field ? $profile->get($telephone_field) : NULL;

    if ($phone && !$phone->isEmpty() && ($phone = $phone->first())) {
      /** @var \Drupal\telephone\Plugin\Field\FieldType\TelephoneItem $phone */
      $this->properties['$phone_number'] = $phone->get('value')->getValue();
    }
  }

}
