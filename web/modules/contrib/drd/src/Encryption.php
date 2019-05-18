<?php

namespace Drupal\drd;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptService;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Encrypt/decrypt sensitive DRD values.
 */
class Encryption {

  use StringTranslationTrait;

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryptionService;

  /**
   * The encryption profile.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile
   */
  protected $encryptionProfile;

  /**
   * The old encryption profile, only set if a profile change happened.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile
   */
  protected $oldEncryptionProfile;

  /**
   * Constructs an Encrypt.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\encrypt\EncryptService $encryptionService
   *   The encryption service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptService $encryptionService = NULL) {
    $this->encryptionService = $encryptionService;
    $profile_id = $config_factory->get('drd.general')->get('encryption_profile');
    if (!empty($profile_id)) {
      $this->encryptionProfile = EncryptionProfile::load($profile_id);
    }
    else {
      drupal_set_message($this->t('Encryption is not configured yet! Go to <a href="@link">settings</a> to fix this!', [
        '@link' => Url::fromRoute('drd.settings')->toString(),
      ]), 'error');
    }
  }

  /**
   * Let the service know that a profile change happened.
   *
   * @param string $old_profile_id
   *   The ID of the old profile.
   * @param string $new_profile_id
   *   The ID of the new profile.
   *
   * @return $this
   */
  public function setOldProfileId($old_profile_id, $new_profile_id) {
    $this->oldEncryptionProfile = NULL;
    $this->encryptionProfile = NULL;
    if (!empty($old_profile_id)) {
      $this->oldEncryptionProfile = EncryptionProfile::load($old_profile_id);
    }
    if (!empty($new_profile_id)) {
      $this->encryptionProfile = EncryptionProfile::load($new_profile_id);
    }
    return $this;
  }

  /**
   * Encrypts the string with the defined profile for DRD.
   *
   * @param string|array $plain
   *   The string (or array) that gets encrypted.
   *
   * @return $this
   */
  public function encrypt(&$plain) {
    if (isset($this->encryptionProfile) && !empty($plain)) {
      if (!empty($this->oldEncryptionProfile)) {
        // We are re-encrypting with a new profile and therefore have to
        // decrypt first with the old profile.
        $profile = $this->encryptionProfile;
        $this->encryptionProfile = $this->oldEncryptionProfile;
        $this->decrypt($plain);
        $this->encryptionProfile = $profile;
      }
      try {
        if (is_array($plain)) {
          foreach ($plain as $key => $value) {
            $this->encrypt($value);
            $plain[$key] = $value;
          }
        }
        else {
          $plain = $this->encryptionService->encrypt($plain, $this->encryptionProfile);
        }
      }
      catch (\Exception $ex) {
        // Let's ignore exceptions, this results in unencrypted operations.
      }
    }
    return $this;
  }

  /**
   * Decrypts the string with the defined profile for DRD.
   *
   * @param string|array $encrypted
   *   The string (or array) that gets decrypted.
   *
   * @return $this
   */
  public function decrypt(&$encrypted) {
    if (isset($this->encryptionProfile) && !empty($encrypted)) {
      try {
        if (is_array($encrypted)) {
          foreach ($encrypted as $key => $value) {
            $this->decrypt($value);
            $encrypted[$key] = $value;
          }
        }
        else {
          $encrypted = $this->encryptionService->decrypt((string) $encrypted, $this->encryptionProfile);
        }
      }
      catch (\Exception $ex) {
        // Let's ignore exceptions, this results in unencrypted operations.
      }
    }
    return $this;
  }

}
