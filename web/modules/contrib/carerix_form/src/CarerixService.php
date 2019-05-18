<?php

namespace Drupal\carerix_form;

use Drupal\file\Entity\File;

/**
 * Class CarerixService.
 *
 * @package Drupal\carerix_form
 */
class CarerixService implements CarerixServiceInterface {

  /**
   * Publication tests with CR Console at.
   *
   * @see https://api.carerix.com/CRPublication/1655
   *
   * Carerix API endpoints foo/describe may be indicating for available fields.
   * Check if any combination can be used to describe application forms per
   * entity.
   *
   * API Summary:
   * @see http://development.wiki.carerix.com/index.php?title=Xml#attachment.xml
   *
   * Carerix duplicate employees handling:
   * > last name + e-mail address
   * > last name + postal code + date of birth
   * > candidate number
   *
   * CR entities:
   * > CRCompany ( company )
   * > CRUser ( contact person )
   * > CRVacancy ( job order )
   * > CRMatch ( match )
   * > CREmployee ( candidate )
   */

  /**
   * Carerix API Rest Client object.
   *
   * @var \Carerix_Api_Rest_Client
   */
  protected $client;

  /**
   * Carerix entity class.
   *
   * @var string
   */
  protected $baseCREntityClass = '\\Carerix_Api_Rest_Entity_CR';

  /**
   * Carerix constructor.
   */
  public function __construct() {
    // Load the client.
    $this->client = carerix_api_client_load();
  }

  /**
   * {@inheritdoc}
   */
  public static function report($message, $status = 'error', $severity = 'error') {
    if (\Drupal::currentUser()->isAuthenticated()) {
      // Set error message.
      drupal_set_message($message, $status);
      // Watchdog.
      if ($severity == 'error') {
        \Drupal::logger('carerix_form')->error($message);
      }
      else {
        \Drupal::logger('carerix_form')->notice($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityById($carerixEntity, $id, array $show = [], $language = 'English') {

    $class = $this->baseCREntityClass . $carerixEntity;

    if (!class_exists($class)) {
      $this->report('Unrecognised Carerix Entity');
      return NULL;
    }

    try {
      return $class::find($id, !empty($show) ? $show : NULL, $language);
    }
    catch (\Carerix_Api_Rest_Exception $e) {
      $this->report($e->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllEntities($carerixEntity, array $params = []) {

    $class = $this->baseCREntityClass . $carerixEntity;

    if (!class_exists($class)) {
      $this->report('Unrecognised Carerix Entity');
      return NULL;
    }

    try {
      return $class::findAll(!empty($params) ? $params : NULL);
    }
    catch (\Carerix_Api_Rest_Exception $e) {
      $this->report($e->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createEmployee(array $values, array $files = [], array $urls = []) {

    // Check type of application submission.
    if (isset($values['pub_id']) && !empty($values['pub_id'])) {
      // Prepare $params.
      $params = ['x-cx-pub' => $values['pub_id']];
      unset($values['pub_id']);
    }
    // Init Carerix entities.
    $user = new \Carerix_Api_Rest_Entity_CRUser();
    $employee = new \Carerix_Api_Rest_Entity_CREmployee();
    // Iterate properties to be set.
    foreach ($values as $property => $value) {
      // Build entity setter method.
      $setMethod = 'set' . ucfirst($property);
      // Iterate Carerix entities.
      foreach (['employee', 'user'] as $entityVar) {
        // Set entity values.
        try {
          // Verify setters.
          if (method_exists(${$entityVar}, $setMethod)
            // Exception for password (buggy). Test it yourself:
            // Method does not exist but can be called on a User object.
            || ($property == 'password' && $entityVar == 'user')) {
            // Set entity value.
            ${$entityVar}->$setMethod($value);
            // Set entity flag.
            ${$entityVar . 'IsCalled'} = TRUE;
            // Skip next entity check.
            break;
          }

        }
        catch (\Carerix_Api_Rest_Exception $e) {
          $this->report($e->getMessage());
        }
      }
    }

    // Associate entities.
    if (isset($userIsCalled) && $userIsCalled) {
      $user->save();
      $employee->setToUser($user);
    }

    if (!empty($files)) {
      // Cron deletes files with status!
      $attachments = [];
      // Init attachments.
      foreach ($files as $attachmentId => $file) {
        // Get file object.
        $fileEntity = File::load($file['fid']);
        $filePath = \Drupal::service('file_system')->realpath($fileEntity->getFileUri());
        // Create attachment.
        $attachment = new \Carerix_Api_Rest_Entity_CRAttachment();
        $attachment->setLabel($attachmentId);
        $attachment->setToTypeNode(['id' => $file['data_node_id']]);
        $attachment->setFilePath($fileEntity->getFilename());
        $attachment->setContent(base64_encode(file_get_contents($filePath)));
        $attachment->save();
        // Gather attachments.
        $attachments[] = $attachment;
      }
      // Associate attachments to employee.
      $employee->setAttachments($attachments);
    }

    if (!empty($urls)) {
      $urlObjects = [];
      // Init urls.
      foreach ($urls as $key => $url) {
        // Create new url.
        $urlObj = new \Carerix_Api_Rest_Entity_CRUrl();
        $urlObj->setUrl($url['url']);
        $urlObj->setToUrlLabel(['id' => $url['data_node_id']]);
        $urlObj->save();
        // Gather.
        $urlObjects[] = $urlObj;
      }
      $user->setUrls($urlObjects);
    }

    // Associate publication.
    try {
      // Apply for pub_id or none.
      if (isset($params)) {
        $employee->apply($params);
      }
      else {
        $employee->apply();
      }
    }
    catch (\Carerix_Api_Rest_Exception $e) {
      $this->report($e->getMessage());
    }

  }

}
