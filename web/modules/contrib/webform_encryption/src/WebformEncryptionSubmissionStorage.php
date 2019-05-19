<?php

namespace Drupal\webform_encryption;

use Drupal\webform\WebformSubmissionStorage;
use Drupal;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Class WebformEncryptionSubmissionStorage.
 *
 * @package Drupal\webform_encryption
 */
class WebformEncryptionSubmissionStorage extends WebformSubmissionStorage {

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $webform_submissions */
    $webform_submissions = parent::loadMultiple($ids);
    $this->loadData($webform_submissions);
    $this->decryptSubmissions($webform_submissions);
    return $webform_submissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave($entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $id = parent::doPreSave($entity);

    $data_original = $entity->getData();

    $webform_id = $entity->getWebform()->id();

    $this->encrypt($data_original, $webform_id);
    $entity->setData($data_original);

    $this->invokeWebformElements('preSave', $entity);
    $this->invokeWebformHandlers('preSave', $entity);
    return $id;
  }

  /**
   * Determine if an element is to be encrypted.
   *
   * @param $element_id
   *   The webform elements machine name.
   *
   * @param $webform_id
   *   The weboforms machine name.
   *
   * @return bool
   */
  public function isEncrypted($element_id, $webform_id) {

    $config = \Drupal::service('config.factory')
      ->get('webform.encryption')
      ->get('element.settings');

    return isset($config[$webform_id][$element_id]['encrypt']) && $config[$webform_id][$element_id]['encrypt'];
  }


  /**
   * Helper function to recursively encrypt fields.
   *
   * @param array $data
   *   The current form array.
   * @param array $config
   *   Configuration for fields to encrypt.
   */
  public function encrypt(array &$data, $webform_id, $encrypted = FALSE) {
    foreach ($data as $key => $value) {

      // If this item is encrypted.
      if ($this->isEncrypted($key, $webform_id) || $encrypted) {
        // We need to track if the parent element is encrypted or not as the children of compounf fields owul have no idea.
        if (!$encrypted) {
          $encrypted = $key;
        }
        if (is_array($value)) {
          // Loop through all the children.
          $this->encrypt($data[$key], $webform_id, $encrypted);
        }
        else {
          $encryption_profile = $this->getEncryptionProfile($encrypted, $webform_id);
          $encrypted_value = Drupal::service('encryption')
            ->encrypt($value, $encryption_profile);
          $data[$key] = $encrypted_value;
        }

      }
      else {
        if (is_array($value)) {
          $this->encrypt($data[$key], $webform_id);
        }
      }
    }

  }

  /**
   * Decrypt a single submissions data
   */
  function decrypt(&$data, $webform_id, $encrypted = FALSE) {

    // Loop through the items.
    foreach ($data as $key => $value) {

      // If this item is encrypted.
      if ($this->isEncrypted($key, $webform_id) || $encrypted) {
        // We need to track if the parent element is encrypted or not as the children of compounf fields owul have no idea.
        if (!$encrypted) {
          $encrypted = $key;
        }
        if (is_array($value)) {
          // Loop through all the children.
          $this->decrypt($data[$key], $webform_id, $encrypted);
        }
        else {
          $encryption_profile = $this->getEncryptionProfile($encrypted, $webform_id);
          $data[$key] = $this->decryptDataField($value, $encryption_profile);
        }

      }
      else {
        if (is_array($value)) {
          $this->decrypt($data[$key], $webform_id);
        }
      }
    }
  }


  /**
   * Function decrypt.
   *
   * @param array $webform_submissions
   *   Array of current webform submissions.
   */
  public function decryptSubmissions(array &$webform_submissions) {

    // Loop through each webform submission (that we are loading on this page).
    foreach ($webform_submissions as $submission_key => $submission) {

      // The id of the webform.
      $webform_id = $submission->getWebform()->id();

      // Retrieve the data to be decrypted.
      $submission_data = $submission->getData();

      // Decrypt it.
      $this->decrypt($submission_data, $webform_id);

      // Set the decrypted data back to the submission.
      $submission->setData($submission_data);

    }
  }

  /**
   * Function decryptDataField.
   *
   * @param string $value
   *   The value to decrypt.
   * @param object $profile
   *   The encryption profile to use.
   *
   * @return mixed
   *   The decrypted string, or the original string if it isn't encrypted.
   */
  public static function decryptDataField($value, $profile) {
    if ($profile) {
      $encryption_profile = EncryptionProfile::load($profile->get('encryption_key'));
      $decrypted_value = Drupal::service('encryption')
        ->decrypt($value, $encryption_profile);
    }
    return $decrypted_value !== FALSE ? $decrypted_value : $value;
  }

  /**
   * Function getEncryptionProfile.
   *
   * @param string $element
   *   The element of which we need an encryption profile for.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null|static
   *   If there is a profile we return that, otherwise FALSE.
   */
  public static function getEncryptionProfile($element, $webform_id) {

    $config = \Drupal::service('config.factory')
      ->get('webform.encryption')
      ->get('element.settings');

    return isset($config[$webform_id][$element]) ? EncryptionProfile::load($config[$webform_id][$element]['encrypt_profile']) : FALSE;
  }

}
