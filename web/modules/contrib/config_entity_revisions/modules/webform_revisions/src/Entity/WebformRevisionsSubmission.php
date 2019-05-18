<?php

namespace Drupal\webform_revisions\Entity;

use Drupal\config_entity_revisions\ConfigEntityRevisionsInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_revisions\WebformRevisionsConfigTrait;
use Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\webform_revisions\Controller\WebformRevisionsController;
use Drupal\Component\Utility\Crypt;

class WebformRevisionsSubmission extends WebformSubmission {

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    $webform_id = $this->webform_id->target_id;
    $revision_id = $this->webform_revision[0];
    if ($revision_id) {
      // EntityReference doesn't understand revisions at the time of writing.
      $revision_id = $revision_id->target_id;

      $revisionsController = WebformRevisionsController::create(\Drupal::getContainer());
      $webform = $revisionsController->loadConfigEntityRevision($revision_id, $webform_id);
      return $webform;
    }

    if (isset($this->webform_id->entity)) {
      return $this->webform_id->entity;
    }
    else {
      return static::$webform;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    if (empty($values['webform_id']) && empty($values['webform'])) {
      if (empty($values['webform_id'])) {
        throw new \Exception('Webform id (webform_id) is required to create a webform submission.');
      }
      elseif (empty($values['webform'])) {
        throw new \Exception('Webform (webform) is required to create a webform submission.');
      }
    }

    // Get temporary webform entity and store it in the static
    // WebformSubmission::$webform property.
    // This could be reworked to use \Drupal\Core\TempStore\PrivateTempStoreFactory
    // but it might be overkill since we are just using this to validate
    // that a webform's elements can be rendered.
    // @see \Drupal\webform\WebformEntityElementsValidator::validateRendering()
    // @see \Drupal\webform_ui\Form\WebformUiElementTestForm::buildForm()
    if (isset($values['webform']) && ($values['webform'] instanceof ConfigEntityRevisionsInterface)) {
      $webform = $values['webform'];
      static::$webform = $values['webform'];
      $values['webform_id'] = $values['webform']->id();
    }
    else {
      /* @var $revisionsController ConfigEntityRevisionsControllerInterface */
      $revisionsController = WebformRevisionsController::create(\Drupal::getContainer());

      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $revisionsController->loadConfigEntityRevision();
      static::$webform = NULL;
    }

    // Get request's source entity parameter.
    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');
    $source_entity = $request_handler->getCurrentSourceEntity('webform');
    $values += [
      'entity_type' => ($source_entity) ? $source_entity->getEntityTypeId() : NULL,
      'entity_id' => ($source_entity) ? $source_entity->id() : NULL,
    ];

    // Decode all data in an array.
    if (empty($values['data'])) {
      $values['data'] = [];
    }
    elseif (is_string($values['data'])) {
      $values['data'] = Yaml::decode($values['data']);
    }

    // Get default date from source entity 'webform' field.
    if ($values['entity_type'] && $values['entity_id']) {
      $source_entity = \Drupal::entityTypeManager()
        ->getStorage($values['entity_type'])
        ->load($values['entity_id']);

      /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
      $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

      if ($webform_field_name = $entity_reference_manager->getFieldName($source_entity)) {
        if ($source_entity->$webform_field_name->target_id == $webform->id() && $source_entity->$webform_field_name->default_data) {
          $values['data'] += Yaml::decode($source_entity->$webform_field_name->default_data);
        }
      }
    }

    // Set default values.
    $current_request = \Drupal::requestStack()->getCurrentRequest();
    $values += [
      'in_draft' => FALSE,
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'token' => Crypt::randomBytesBase64(),
      'uri' => preg_replace('#^' . base_path() . '#', '/', $current_request->getRequestUri()),
      'remote_addr' => ($webform && $webform->isConfidential()) ? '' : $current_request->getClientIp(),
    ];

    $webform->invokeHandlers(__FUNCTION__, $values);
    $webform->invokeElements(__FUNCTION__, $values);
  }

}
