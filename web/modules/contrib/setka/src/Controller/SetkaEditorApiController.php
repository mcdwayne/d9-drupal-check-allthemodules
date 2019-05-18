<?php

namespace Drupal\setka_editor\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\setka_editor\SetkaEditorApi;
use Drupal\setka_editor\SetkaEditorHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Setka Editor API controller.
 */
class SetkaEditorApiController extends ControllerBase {

  const SETKA_ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/pjpeg',
    'image/gif',
    'image/png',
    'image/svg+xml',
    'image/vnd.wap.wbmp',
  ];

  /**
   * Setka Editor api service.
   *
   * @var \Drupal\setka_editor\SetkaEditorApi
   */
  protected $editorApi;

  /**
   * Setka Editor config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $setkaConfig;

  /**
   * Service to interact with $_SESSION.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $sessionStore;

  /**
   * Drupal file usage interface.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Drupal database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Cache Discovery bin backend.
   *
   * @var \Drupal\Core\Cache\DatabaseBackend
   */
  protected $cacheDiscovery;

  /**
   * Drupal CSS optimizer service.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $cssOptimizer;

  /**
   * Drupal JS optimizer service.
   *
   * @var \Drupal\Core\Asset\JsCollectionOptimizer
   */
  protected $jsOptimizer;

  /**
   * Drupal file_system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Drupal queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  public function __construct(SetkaEditorApi $editorApi,
                              ConfigFactory $configFactory,
                              PrivateTempStoreFactory $privateTempstore,
                              FileUsageInterface $fileUsage,
                              Connection $database,
                              DatabaseBackend $cacheDiscovery,
                              CssCollectionOptimizer $cssOptimizer,
                              JsCollectionOptimizer $jsOptimizer,
                              FileSystem $fileSystem,
                              QueueFactory $queueFactory,
                              LibraryDiscovery $libraryDiscovery,
                              LockBackendInterface $lock) {
    $this->editorApi = $editorApi;
    $this->configFactory = $configFactory;
    $this->setkaConfig = $configFactory->get('setka_editor.settings');
    $this->sessionStore = $privateTempstore->get('setka_editor');
    $this->fileUsage = $fileUsage;
    $this->database = $database;
    $this->cacheDiscovery = $cacheDiscovery;
    $this->cssOptimizer = $cssOptimizer;
    $this->jsOptimizer = $jsOptimizer;
    $this->fileSystem = $fileSystem;
    $this->queueFactory = $queueFactory;
    $this->libraryDiscovery = $libraryDiscovery;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('setka_editor.api'),
      $container->get('config.factory'),
      $container->get('user.private_tempstore'),
      $container->get('file.usage'),
      $container->get('database'),
      $container->get('cache.discovery'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('file_system'),
      $container->get('queue'),
      $container->get('library.discovery'),
      $container->get('lock')
    );
  }

  /**
   * API gate to get setka editor files.
   */
  public function editorConfig(Request $request) {
    $token = $request->request->get('token');
    $data = $request->request->get('data');
    $licenseKey = $this->setkaConfig->get('setka_license_key');
    $setkaUseCdn = $this->setkaConfig->get('setka_use_cdn');
    $downloadFiles = (empty($setkaUseCdn) && SetkaEditorHelper::checkSetkaFolderPermissions($this->fileSystem));
    if (!empty($data) && !empty($token) && $licenseKey == $token) {
      if (!empty($data['plugins']) && !empty($data['content_editor_version'])
        && !empty($data['public_token']) && !empty($data['theme_files']) && !empty($data['content_editor_files'])) {
        $newSettings = SetkaEditorHelper::parseStyleManagerData($data);
      }
      if (!empty($newSettings['setka_editor_js_cdn']) && !empty($newSettings['setka_editor_css_cdn']) &&
        !empty($newSettings['setka_company_css_cdn']) && !empty($newSettings['setka_company_json_cdn'] &&
          !empty($newSettings['setka_public_js_cdn']))) {
        if ($downloadFiles) {
          $queue = $this->queueFactory->get('update_setke_editor');
          if (!$queue->numberOfItems()) {
            $queue->createQueue();
          }
          $queue->createItem(['newSettings' => $newSettings]);
          if ($this->lock->acquire('setka_editor_files_update')) {
            while ($newSettingsItem = $queue->claimItem()) {
              $newSettingsData = $newSettingsItem->data['newSettings'];
              SetkaEditorHelper::buildSetkaFilesUpdateTask($this->setkaConfig, $this->state(), $newSettingsData);
              $this->configFactory->getEditable('setka_editor.settings')
                ->set('setka_editor_version', $newSettingsData['setka_editor_version'])
                ->set('setka_editor_public_token', $newSettingsData['setka_editor_public_token'])
                ->set('setka_company_meta_data', $newSettingsData['setka_company_meta_data'])
                ->set('setka_editor_js_cdn', $newSettingsData['setka_editor_js_cdn'])
                ->set('setka_editor_css_cdn', $newSettingsData['setka_editor_css_cdn'])
                ->set('setka_company_css_cdn', $newSettingsData['setka_company_css_cdn'])
                ->set('setka_company_json_cdn', $newSettingsData['setka_company_json_cdn'])
                ->set('setka_public_js_cdn', $newSettingsData['setka_public_js_cdn'])
                ->save();
              $this->libraryDiscovery->clearCachedDefinitions();
              $this->configFactory->reset('setka_editor.settings');
              SetkaEditorHelper::runSetkaFilesUpdateTask($this->state());
              $queue->deleteItem($newSettingsItem);
            }
            foreach (Cache::getBins() as $cache_backend) {
              $cache_backend->deleteAll();
            }
            $this->libraryDiscovery->clearCachedDefinitions();
            $this->configFactory->reset('setka_editor.settings');
            $this->cacheDiscovery->deleteAll();
            $this->cssOptimizer->deleteAll();
            $this->jsOptimizer->deleteAll();
            _drupal_flush_css_js();

            $this->lock->release('setka_editor_files_update');
          }
        }
        else {
          $this->configFactory->getEditable('setka_editor.settings')
            ->set('setka_editor_version', $newSettings['setka_editor_version'])
            ->set('setka_editor_public_token', $newSettings['setka_editor_public_token'])
            ->set('setka_company_meta_data', $newSettings['setka_company_meta_data'])
            ->set('setka_editor_js_cdn', $newSettings['setka_editor_js_cdn'])
            ->set('setka_editor_css_cdn', $newSettings['setka_editor_css_cdn'])
            ->set('setka_company_css_cdn', $newSettings['setka_company_css_cdn'])
            ->set('setka_company_json_cdn', $newSettings['setka_company_json_cdn'])
            ->set('setka_public_js_cdn', $newSettings['setka_public_js_cdn'])
            ->save();
          $this->state()->setMultiple(
            [
              'setka_editor_js' => FALSE,
              'setka_editor_css' => FALSE,
              'setka_company_css' => FALSE,
              'setka_company_json' => FALSE,
              'setka_public_js' => FALSE,
            ]
          );
          $this->getLogger('setka_editor')->info('Setka Editor config update: successful update!');
          foreach (Cache::getBins() as $cache_backend) {
            $cache_backend
              ->deleteAll();
          }
          $this->libraryDiscovery->clearCachedDefinitions();
          $this->configFactory->reset('setka_editor.settings');
          $this->cacheDiscovery->deleteAll();
          $this->cssOptimizer->deleteAll();
          $this->jsOptimizer->deleteAll();
          _drupal_flush_css_js();
        }
      }
      else {
        $this->getLogger('setka_editor')->error('Setka Editor config update error: required request data does not exist!');
      }
    }

    return new JsonResponse();
  }

  /**
   * Gate for Setka Editor API to upload images.
   */
  public function uploadImages(Request $request) {
    $validUuids = $this->sessionStore->get('setka_editor_valid_uuids');
    $entityUuid = $request->request->get('entityUuid');
    $entityId = $request->request->get('entityId');
    $entityType = $request->request->get('entityType');
    if ($this->checkEntityEditAccess($entityId, $entityType)) {
      if ($validUuids && $entityUuid && in_array($entityUuid, $validUuids)) {
        if (mb_strpos($request->headers->get('Content-Type'), 'multipart/form-data;') !== 0) {
          $res = new Response();
          $res->setStatusCode(400, $this->t('Unsupported content type.'));
          return $res;
        }
        $uploadError = FALSE;
        switch ($_FILES['file']['error']) {
          case UPLOAD_ERR_INI_SIZE:
            $uploadError = $this->t('File to large. Max file size: @size',
              ['@size' => ini_get('upload_max_filesize')]
            );
            break;

          case UPLOAD_ERR_FORM_SIZE:
            $uploadError = $this->t('File to large. Max file size: @size',
              ['@size' => ini_get('upload_max_filesize')]
            );
            break;

          case UPLOAD_ERR_PARTIAL:
            $uploadError = $this->t('File has uploaded partially.');
            break;

          case UPLOAD_ERR_NO_FILE:
            $uploadError = $this->t('No file uploaded.');
            break;

          case UPLOAD_ERR_NO_TMP_DIR:
            $uploadError = $this->t('Tmp directory does not exists.');
            break;

          case UPLOAD_ERR_CANT_WRITE:
            $uploadError = $this->t('Write error.');
            break;

          case UPLOAD_ERR_EXTENSION:
            $uploadError = $this->t('Extension has stopped upload.');
            break;
        }
        if ($uploadError) {
          $res = new Response();
          $res->setStatusCode(400, $uploadError);
          return $res;
        }
        $mime = $_FILES['file']['type'];
        if (!in_array($mime, self::SETKA_ALLOWED_MIME_TYPES)) {
          $res = new Response();
          $res->setStatusCode(400, $this->t('Unsupported mime type.'));
          return $res;
        }
        $directory = $this->fileSystem->realpath("public://setka");
        $directoryError = FALSE;
        if (!SetkaEditorHelper::checkSetkaFolderPermissions($this->fileSystem)) {
          if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
            $directoryError = TRUE;
            $this->getLogger('setka_editor')->error('The directory %directory does not exist or is not writable.', ['%directory' => $directory]);
          }
        }
        if ($directoryError) {
          $res = new Response();
          $res->setStatusCode(400, $this->t('Image directory permissions error.'));
          return $res;
        }
        else {
          $fileData = file_get_contents($_FILES['file']['tmp_name']);
          $file = file_save_data($fileData, 'public://setka/' . $_FILES['file']['name'], FILE_EXISTS_RENAME);
          $imageId = $file->id();
          $imageUrl = file_create_url($file->getFileUri());

          if ($entityId && $entityType && $entityId != 'null') {
            $this->fileUsage->add($file, 'setka_editor', $entityType, $entityId);
          }
          else {
            $setkaEditorImages = $this->sessionStore->get('setka_editor_images') ?? [];
            $setkaEditorImages[$entityUuid][] = $imageId;
            $this->sessionStore->set('setka_editor_images', $setkaEditorImages);
          }

          return new JsonResponse([
            'id' => $imageId,
            'url' => $imageUrl,
          ]);
        }
      }
    }
    $res = new Response();
    $res->setStatusCode(400, $this->t('Unknown error.'));
    return $res;
  }

  /**
   * Gate for Setka Editor API to edit image alt.
   */
  public function putImage($id, Request $request) {
    $requestContent = $request->getContent();
    if ($requestPayload = Json::decode($requestContent)) {
      $imageId = (int) $id;
      if (($requestPayload['entityId'] || $requestPayload['entityUuid']) && !empty($requestPayload['entityType']) && $requestPayload['alt'] && $imageId > 0) {
        if ($this->checkEntityEditAccess($requestPayload['entityId'], $requestPayload['entityType'])) {
          /** @var \Drupal\file\Entity\File $imageEntity */
          $imageEntity = $this->entityTypeManager()->getStorage('file')->load($imageId);
          if ($this->checkImageAttachedToEntity($imageEntity, $requestPayload['entityId'], $requestPayload['entityUuid'])) {
            $imageUri = $imageEntity->getFileUri();
            $imageUrl = file_create_url($imageUri);

            $query = $this->database->update('file_managed');
            $query->fields(['alt' => $requestPayload['alt']]);
            $query->condition('fid', $id);
            $query->execute();
            return new JsonResponse([
              'id' => $imageId,
              'url' => $imageUrl,
              'thumbUrl' => $imageUrl,
            ]);
          }
        }
        else {
          $res = new Response();
          $res->setStatusCode(400, $this->t('Permissions error.'));
          return $res;
        }
      }
    }
    $res = new Response();
    $res->setStatusCode(400, $this->t('Unknown error.'));
    return $res;
  }

  /**
   * Gate for Setka Editor API to delete image.
   */
  public function delImage($id, Request $request) {
    $requestContent = $request->getContent();
    if ($requestPayload = Json::decode($requestContent)) {
      $imageId = (int) $id;
      if (($requestPayload['entityId'] || $requestPayload['entityUuid']) && !empty($requestPayload['entityType']) && $imageId > 0) {
        if ($this->checkEntityEditAccess($requestPayload['entityId'], $requestPayload['entityType'])) {
          $imageEntity = $this->entityTypeManager()->getStorage('file')->load($imageId);
          if ($this->checkImageAttachedToEntity($imageEntity, $requestPayload['entityId'], $requestPayload['entityUuid'])) {
            if ($imageEntity) {
              $imageEntity->delete();
            }
            $setkaEditorImages = $this->sessionStore->get('setka_editor_images') ?? [];
            if (!empty($setkaEditorImages[$requestPayload['entityUuid']])) {
              $imageKey = array_search($imageId, $setkaEditorImages[$requestPayload['entityUuid']]);
              unset($setkaEditorImages[$requestPayload['entityUuid']][$imageKey]);
              $this->sessionStore->set('setka_editor_images', $setkaEditorImages);
            }
          }
          return new JsonResponse();
        }
      }
    }
    $res = new Response();
    $res->setStatusCode(400, $this->t('Unknown error.'));
    return $res;
  }

  /**
   * Checks if user has access to edit/create entity.
   *
   * @param int|null $entityId
   *   Entity id.
   * @param string $entityType
   *   Entity type.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   Check access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function checkEntityEditAccess($entityId, $entityType) {
    $account = $this->currentUser();
    if ($entityId > 0) {
      $entity = $this->entityTypeManager()->getStorage($entityType)->load($entityId);
      return $entity->access('edit', $account);
    }
    else {
      return $account->hasPermission('create ' . $entityType . ' content');
    }
  }

  /**
   * Checks if image attached to entity or not.
   *
   * @param \Drupal\file\Entity\File $imageFile
   *   Image file.
   * @param int $entityId
   *   Entity id.
   * @param string $entityUuid
   *   Entity uuid.
   *
   * @return bool
   *   Image attach status.
   */
  protected function checkImageAttachedToEntity(File $imageFile, $entityId, $entityUuid) {
    if (!$imageFile) {
      return FALSE;
    }
    if ($entityId) {
      $listUsage = $this->fileUsage->listUsage($imageFile);
      if (!empty($listUsage['setka_editor'])) {
        foreach ($listUsage['setka_editor'] as $entityTypeUsage) {
          if ($entityTypeUsage[$entityId]) {
            return TRUE;
          }
        }
      }
      return FALSE;
    }
    elseif ($entityUuid) {
      $setkaEditorImages = $this->sessionStore->get('setka_editor_images') ?? [];
      if (!empty($setkaEditorImages[$entityUuid])) {
        return in_array($imageFile->id(), $setkaEditorImages[$entityUuid]);
      }
    }
    return FALSE;
  }

}
