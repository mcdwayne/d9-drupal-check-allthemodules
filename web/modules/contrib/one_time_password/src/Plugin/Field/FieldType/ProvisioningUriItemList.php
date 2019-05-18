<?php

namespace Drupal\one_time_password\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\one_time_password\Exception\MissingProvisioningUriException;
use OTPHP\TOTP;

/**
 * A field list for the provisioning URI field.
 *
 * @internal
 */
class ProvisioningUriItemList extends FieldItemList {

  /**
   * Numerically indexed array of field items.
   *
   * @var \Drupal\one_time_password\Plugin\Field\FieldType\ProvisioningUriItem[]
   */
  protected $list = [];

  /**
   * Regenerate the one time password provisioning URI.
   */
  public function regenerateOneTimePassword() {
    // Only use the default 30 seconds and SHA1 hash because google
    // authenticator will ignore other configurations.
    $password = new TOTP($this->getEntity()->label(), NULL);
    $this->list[0] = $this->createItem(0, [
      'uri' => $password->getProvisioningUri()
    ]);
  }

  /**
   * Get the one time password object for the field item list.
   *
   * @return \OTPHP\TOTP
   *   The one time password object.
   *
   * @throws \Drupal\one_time_password\Exception\MissingProvisioningUriException
   *   Throws an exception if there is no items to build the password object.
   */
  public function getOneTimePassword() {
    if ($this->count() !== 1) {
      throw new MissingProvisioningUriException('Cannot get password, provisioning field is empty.');
    }
    return $this->list[0]->getOneTimePassword();
  }

}
