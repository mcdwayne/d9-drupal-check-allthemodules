<?php

namespace Drupal\media_download_all\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class DownloadController.
 */
class DownloadController extends ControllerBase {

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DownloadController object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current User.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, FileSystem $file_system, Messenger $messenger, AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * The main download method.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   * @param string $field_name
   *   The field name.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return the file.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Archiver\ArchiverException
   */
  public function download($entity_type, $entity_id, $field_name) {
    // Cache ID to store files for this entity.
    $cid = 'media_download_all:' . $entity_type . ':' . $entity_id;

    // Check for caches files for this entity.
    $cache = \Drupal::cache()->get($cid);
    if ($cache) {
      $cached_files = $cache->data;
    }

    // If the file already exists, no need to recreate it.
    if (isset($cached_files[$field_name]) && file_exists($cached_files[$field_name])) {
      return $this->streamZipFile($cached_files[$field_name]);
    }
    else {
      $files = $this->getFiles($entity_type, $entity_id, $field_name);

      $redirect_on_error_to = empty($_SERVER['HTTP_REFERER']) ? '/' : $_SERVER['HTTP_REFERER'];

      if (count($files) === 0) {
        $this->messenger->addError($this->t('No files found for this entity to be downloaded'));
        return new RedirectResponse($redirect_on_error_to);
      }

      $zip_files_directory = "private://media_download_all";
      if (file_prepare_directory($zip_files_directory, FILE_CREATE_DIRECTORY)) {
        $operations = [];

        foreach ($files as $fid => $file_name) {
          $operations[] = [
            '\Drupal\media_download_all\ProcessingBatch::operation',
            [$entity_type, $entity_id, $field_name, $fid, $file_name],
          ];
        }

        $batch = [
          'title' => $this->t('Preparing Download All...'),
          'operations' => $operations,
          'finished' => '\Drupal\media_download_all\ProcessingBatch::operationFinished',
        ];

        batch_set($batch);
        return batch_process('applyto');
      }
      $this->messenger->addError($this->t('Zip file directory not found.'));
      return new RedirectResponse($redirect_on_error_to);
    }
  }

  /**
   * Get files associated with the entity .
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The file IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getFiles($entity_type, $entity_id, $field_name) {
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type);
    $entity = $entity_storage->load($entity_id);
    $media_files = $entity->{$field_name};
    $media_ids = [];

    foreach ($media_files->getValue() as $item) {
      if (isset($item['target_id'])) {
        $media_ids[] = $item['target_id'];
      }
    }

    $media_storage = $this->entityTypeManager()->getStorage('media');
    $media_entities = $media_storage->loadMultiple($media_ids);

    $files = [];

    foreach ($media_entities as $media) {
      $bundle = $media->bundle();
      $file_field_names = $this->getFileFieldsOfBundle($bundle);
      foreach ($file_field_names as $file_field_name) {
        foreach ($media->{$file_field_name}->getValue() as $item) {
          if (isset($item['target_id'])) {
            $files[$item['target_id']] = $media->getName();
          }
        }
      }
    }

    return $files;
  }

  /**
   * Get file field names.
   *
   * @param string $bundle
   *   The bundle of media.
   *
   * @return array
   *   Filed names contains file.
   */
  protected function getFileFieldsOfBundle($bundle) {

    $entityFieldManager = $this->entityFieldManager;

    $field_definitions = $entityFieldManager->getFieldDefinitions('media', $bundle);

    $field_names_filtered = [];
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_name !== "thumbnail" && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'file') {
        $field_names_filtered[] = $field_name;
      }
    }

    return $field_names_filtered;
  }

  /**
   * Method to stream created zip file.
   *
   * @param string $file_path
   *   File physical path.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The binary file.
   */
  protected function streamZipFile($file_path) {
    $binary_file_response = new BinaryFileResponse($file_path);
    $binary_file_response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($file_path));
    return $binary_file_response;
  }

  /**
   * Checks access for this controller.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Return the result of the access check   *
   */
  public function access($entity_type, $entity_id, $field_name) {
    // Require permission to view the entity AND "View media" permission.
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $entity_storage->load($entity_id);

    if ($this->currentUser->hasPermission('view media')) {
      return $entity->access('view', NULL, TRUE);
    }

    return AccessResult::forbidden();
  }

}
