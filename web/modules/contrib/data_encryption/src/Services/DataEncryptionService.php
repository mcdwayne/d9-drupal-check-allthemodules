<?php

namespace Drupal\data_encryption\Services;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\encrypt\EncryptService;

/**
 * Class DataEncryptionService.
 *
 * @package Drupal\data_encryption
 */
class DataEncryptionService {

  /**
   * Logger Channel Factory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Entity Manager Interface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Encrypt Service definition.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryptService;

  /**
   * Constructs Dependent Services.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory Service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   EntityManagerInterface Service.
   * @param \Drupal\encrypt\EncryptService $encryptService
   *   EncryptService Service.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, EntityManagerInterface $entityManager, EncryptService $encryptService) {
    $this->loggerFactory = $loggerFactory->get('data_encryption');
    $this->entityManager = $entityManager;
    $this->encryptService = $encryptService;
  }

  /**
   * Validate the Encryption Profile.
   *
   * @param string $profile
   *   Encryption Profile name.
   *
   * @return array
   *   Encrption Profile Entity.
   */
  public function checkEncryptionProfile($profile) {
    $encryptionProfile = $this->entityManager->getStorage('encryption_profile')->load($profile);
    if (empty($encryptionProfile)) {
      $this->loggerFactory->notice('Encrption Profile is not available.');
      return FALSE;
    }
    else {
      return $encryptionProfile;
    }
  }

  /**
   * Get Encrypted Values.
   *
   * @param string $value
   *   Value to be Encrypted.
   * @param string $profile
   *   Encription Profile.
   *
   * @return string
   *   Encrpted Value.
   */
  public function getEncryptedValue($value, $profile) {

    $encryptionProfile = $this->checkEncryptionProfile($profile);
    if ($encryptionProfile) {
      $encryptedValue = $this->encryptService->encrypt($value, $encryptionProfile);
      return $encryptedValue;
    }
  }

  /**
   * Get Encrypted Values.
   *
   * @param string $value
   *   Value to be Encrypted.
   * @param string $profile
   *   Encription Profile.
   *
   * @return string
   *   Decrypted Value.
   */
  public function getDecryptedValue($value, $profile) {
    $encryptionProfile = $this->checkEncryptionProfile($profile);
    if ($encryptionProfile) {
      $decryptedValue = $this->encryptService->decrypt($value, $encryptionProfile);
      return $decryptedValue;
    }
  }

}
