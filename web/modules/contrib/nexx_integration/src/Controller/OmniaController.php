<?php

namespace Drupal\nexx_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Utility\Token;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Class OmniaController.
 *
 * @package Drupal\nexx_integration\Controller
 */
class OmniaController extends ControllerBase {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity;

  /**
   * The media entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaEntityStorage;

  /**
   * The media entity definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $mediaEntityDefinition;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Response data from Nexx.
   *
   * @var object
   */
  protected $videoData;

  /**
   * Video data.
   *
   * @var array
   */
  protected $nexxVideoData = [];

  /**
   * OmniaController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException.
   */
  public function __construct(
    Connection $database,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager,
    LoggerInterface $logger,
    Token $token
  ) {
    $this->database = $database;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->logger = $logger;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('logger.factory')->get('nexx_integration'),
      $container->get('token')
    );
  }

  /**
   * Search and edit videos.
   */
  public function videoList() {
    return [
      '#theme' => 'omnia_editor',
      '#auth_key' => $this->config('nexx_integration.settings')
        ->get('nexx_api_authkey'),
    ];
  }

  /**
   * Retrieve video data field name.
   *
   * @return \string
   *   The name of the field.
   *
   * @throws \Exception
   */
  protected function getVideoFieldName() {
    $entity_type_id = 'media';
    $videoBundle = $this->config('nexx_integration.settings')
      ->get('video_bundle');

    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $videoBundle);
    foreach ($fieldDefinitions as $fieldname => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'nexx_video_data') {
        $videoField = $fieldname;
        break;
      }
    }

    if (empty($videoField)) {
      throw new \Exception('No video data field defined');
    }

    return $videoField;
  }

  /**
   * Check if token is valid.
   *
   * @return bool
   *   Validity of token.
   *
   * @throws \Exception
   */
  protected function isTokenValid($request) {
    $token = $request->query->get('token', NULL);

    if ($token === NULL) {
      throw new AccessDeniedHttpException();
    }

    $config = $this->config('nexx_integration.settings');
    if ($token != $config->get('notification_access_key')) {
      throw new AccessDeniedHttpException();
    }

    return TRUE;
  }

  /**
   * Return Nexx Id of video.
   *
   * @return null|int
   *   Nexx Id of video.
   */
  protected function getVideoId() {
    return !empty($this->videoData->itemID) ? $this->videoData->itemID : 0;
  }

  /**
   * Return Nexx API trigger reason.
   *
   * @return null|string
   *   Nexx API trigger reason.
   */
  protected function getTriggerReason() {
    return !empty($this->videoData->triggerReason) ? (string) $this->videoData->triggerReason : '';
  }

  /**
   * Check if video is deleted.
   *
   * @return bool
   *   Is video deleted.
   */
  protected function isVideoDeleted() {
    return !empty($this->videoData->itemData->publishingdata->isDeleted) ? (bool) $this->videoData->itemData->publishingdata->isDeleted : 0;
  }

  /**
   * Retrieves video bundle.
   *
   * @return \string
   *   Nexx video bundle.
   */
  protected function getVideoBundle() {
    return $this->config('nexx_integration.settings')
      ->get('video_bundle');
  }

  /**
   * Retrieve video data field name.
   *
   * @return \string
   *   The name of the field.
   *
   * @throws \Exception
   */
  protected function videoFieldName() {
    $entity_type_id = 'media';
    $videoBundle = $this->getVideoBundle();

    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $videoBundle);
    foreach ($fieldDefinitions as $fieldname => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'nexx_video_data') {
        $videoField = $fieldname;
        break;
      }
    }

    if (empty($videoField)) {
      throw new \Exception('No video data field defined');
    }

    return $videoField;
  }

  /**
   * Endpoint for video creation / update.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   *
   * @throws \Exception
   */
  public function video(Request $request) {
    if (!$this->isTokenValid($request)) {
      $this->logger->error('Wrong or missing token in request.');
      throw new AccessDeniedHttpException();
    }

    $content = $request->getContent();
    $this->videoData = json_decode($content);

    if (!is_object($this->videoData)) {
      throw new ServiceUnavailableHttpException();
    }

    if (!$this->getVideoId()) {
      throw new ServiceUnavailableHttpException();
    }

    $this->logger->info('Incoming video "@title" (nexx id: @id)',
      [
        '@title' => !empty($this->videoData->itemData->general->title) ? (string) $this->videoData->itemData->general->title : '',
        '@id' => $this->getVideoId(),
      ]
    );

    $video_field = $this->getVideoFieldName();
    /** @var \Drupal\Core\Entity\EntityInterface $mediaStorage */
    $mediaStorage = $this->entityTypeManager()
      ->getStorage('media');
    $ids = $mediaStorage->getQuery()
      ->condition($video_field . '.item_id', $this->getVideoId())
      ->range(0, 1)
      ->sort('created', 'DESC')
      ->execute();

    $response = new JsonResponse();
    // Delete video if incomming isDeleted is 1.
    if ($this->getTriggerReason() == 'delete' || $this->isVideoDeleted()) {
      if ($id = array_pop($ids)) {
        $media = $mediaStorage->load($id);
        $media->delete();
        $this->logger->info('Deleted video "@title" (Drupal id: @id)',
          [
            '@title' => $media->label(),
            '@id' => $id,
          ]
        );
        $response->setData([
          'refnr' => $this->getVideoId(),
          'value' => $id,
        ]);
        return $response;
      }
      $response->setData([
        'refnr' => $this->getVideoId(),
        'value' => NULL,
      ]);
      return $response;
    }

    // Edit video.
    elseif ($id = array_pop($ids)) {
      $media = $mediaStorage->load($id);
      $this->logger->info('Updated video "@title" (Drupal id: @id)',
        [
          '@title' => !empty($this->videoData->itemData->general->title) ? (string) $this->videoData->itemData->general->title : '',
          '@id' => $id,
        ]
      );
    }

    // Create video.
    else {
      $media = $mediaStorage->create(['bundle' => $this->getVideoBundle()]);
      $this->logger->info('Created video "@title" (Drupal id: @id)',
        [
          '@title' => !empty($this->videoData->itemData->general->title) ? (string) $this->videoData->itemData->general->title : '',
          '@id' => $media->uuid(),
        ]
      );
    }

    $this->mapData($media);
    $this->setState($media);

    $media->save();
    $response->setData([
      'refnr' => $this->getVideoId(),
      'value' => $media->id(),
    ]);
    return $response;
  }

  /**
   * Prepare data from Nexx APi call.
   */
  protected function prepareData() {
    $teaser = substr($this->videoData->itemData->general->teaser, 0, 256);
    $description = substr($this->videoData->itemData->general->description, 0, 256);
    $altdescription = substr($this->videoData->itemData->general->altdescription, 0, 256);

    return $this->nexxVideoData = [
      'item_id' => $this->getVideoId(),
      'title' => !empty($this->videoData->itemData->general->title) ? (string) $this->videoData->itemData->general->title : '',
      'hash' => !empty($this->videoData->itemData->general->hash) ? (string) $this->videoData->itemData->general->hash : '',
      'alttitle' => !empty($this->videoData->itemData->general->alttitle) ? (string) $this->videoData->itemData->general->alttitle : '',
      'subtitle' => !empty($this->videoData->itemData->general->subtitle) ? (string) $this->videoData->itemData->general->subtitle : '',
      'teaser' => !empty($teaser) ? $teaser : '',
      'description' => !empty($description) ? $description : '',
      'altdescription' => !empty($altdescription) ? $altdescription : '',
      'copyright' => !empty($this->videoData->itemData->general->copyright) ? (string) $this->videoData->itemData->general->copyright : '',
      'actors_ids' => !empty($this->videoData->itemData->general->persons_raw) ? (string) $this->videoData->itemData->general->persons_raw : '',
      'tags_ids' => !empty($this->videoData->itemData->general->tags_raw) ? (string) $this->videoData->itemData->general->tags_raw : '',
      'channel_id' => !empty($this->videoData->itemData->channeldata->ID) ? (int) $this->videoData->itemData->channeldata->ID : 0,
      'uploaded' => !empty($this->videoData->itemData->general->uploaded) ? $this->videoData->itemData->general->uploaded : NULL,
      'active' => !empty($this->videoData->itemData->publishingdata->isPublished) ? (int) $this->videoData->itemData->publishingdata->isPublished : 0,
      'isDeleted' => !empty($this->videoData->itemData->publishingdata->isDeleted) ? (int) $this->videoData->itemData->publishingdata->isDeleted : 0,
      'isBlocked' => !empty($this->videoData->itemData->publishingdata->isBlocked) ? (int) $this->videoData->itemData->publishingdata->isBlocked : 0,
      'runtime' => !empty($this->videoData->itemData->general->runtime) ? (string) $this->videoData->itemData->general->runtime : '00:00:00',

      'isSSC' => !empty($this->videoData->itemData->publishingdata->allowedOnDesktop) ? (int) $this->videoData->itemData->publishingdata->allowedOnDesktop : 0,
      'validfrom_ssc' => !empty($this->videoData->itemData->publishingdata->validFromDesktop) ? (int) $this->videoData->itemData->publishingdata->validFromDesktop : 0,
      'validto_ssc' => !empty($this->videoData->itemData->publishingdata->validUntilDesktop) ? (int) $this->videoData->itemData->publishingdata->validUntilDesktop : 0,
      'encodedSSC' => !empty($this->videoData->itemData->publishingdata->isEncoded) ? (int) $this->videoData->itemData->publishingdata->isEncoded : 0,

      'isHYVE' => !empty($this->videoData->itemData->publishingdata->allowedOnSmartTV) ? (int) $this->videoData->itemData->publishingdata->allowedOnSmartTV : 0,
      'validfrom_hyve' => !empty($this->videoData->itemData->publishingdata->validFromSmartTV) ? (int) $this->videoData->itemData->publishingdata->validFromSmartTV : 0,
      'validto_hyve' => !empty($this->videoData->itemData->publishingdata->validUntilSmartTV) ? (int) $this->videoData->itemData->publishingdata->validUntilSmartTV : 0,
      'encodedHYVE' => !empty($this->videoData->itemData->publishingdata->isEncoded) ? (int) $this->videoData->itemData->publishingdata->isEncoded : 0,

      'isMOBILE' => !empty($this->videoData->itemData->publishingdata->allowedOnMobile) ? (int) $this->videoData->itemData->publishingdata->allowedOnMobile : 0,
      'validfrom_mobile' => !empty($this->videoData->itemData->publishingdata->validFromMobile) ? (int) $this->videoData->itemData->publishingdata->validFromMobile : 0,
      'validto_mobile' => !empty($this->videoData->itemData->publishingdata->validUntilMobile) ? (int) $this->videoData->itemData->publishingdata->validUntilMobile : 0,
      'encodedMOBILE' => !empty($this->videoData->itemData->publishingdata->isEncoded) ? (int) $this->videoData->itemData->publishingdata->isEncoded : 0,
    ];
  }

  /**
   * Map incoming nexx video data to media entity fields.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   */
  protected function mapData(MediaInterface $media) {
    $entityType = $this->entityTypeManager()->getDefinition('media');
    $videoField = $this->videoFieldName();

    // Prepare nexxVideoData from JSON send by NEXX and
    // try to get existing thumbnail URL from existing video
    // to fix re-adding thumb media image on every update push.
    $this->prepareData();
    if (!$media->$videoField->isEmpty()) {
      $this->nexxVideoData['thumb'] = $media->$videoField->thumb;
    }
    $media->set($videoField, $this->nexxVideoData);

    $actor_ids = explode(',', $this->nexxVideoData['actors_ids']);
    $tag_ids = explode(',', $this->nexxVideoData['tags_ids']);

    // Copy title to label field.
    $labelKey = $entityType->getKey('label');
    $media->$labelKey = $this->nexxVideoData['title'];

    $media_config = $media->getSource()->getConfiguration();

    $fields = [
      'description_field' => NULL,
      'channel_field' => NULL,
      'actor_field' => NULL,
      'tag_field' => NULL,
      'teaser_image_field' => NULL,
    ];

    foreach ($fields as $k => $v) {
      if (array_key_exists($k, $media_config)) {
        $fields[$k] = $media_config[$k];
      }
    }

    // Map the description.
    if (!empty($fields['description_field'])) {
      $media->set($fields['description_field'], $this->nexxVideoData['description']);
    }

    // Update taxonomy references.
    if (!empty($fields['channel_field']) && !empty($this->nexxVideoData['channel_id'])) {
      $term_id = $this->mapTermId($this->nexxVideoData['channel_id'], 'channel');
      if (!empty($term_id)) {
        $media->set($fields['channel_field'], $term_id);
      }
      else {
        $this->logger->warning('Unknown ID @term_id for term "@term_name"',
          [
            '@term_id' => $this->nexxVideoData['channel_id'],
            '@term_name' => $this->nexxVideoData['title'],
          ]
        );
      }
    }

    if (!empty($fields['actor_field'])) {
      $mapped_actor_ids = $this->mapMultipleTermIds($actor_ids, 'star');
      $media->set($fields['actor_field'], $mapped_actor_ids);
    }

    if (!empty($fields['tag_field'])) {
      $mapped_tag_ids = $this->mapMultipleTermIds($tag_ids, 'tags');
      $media->set($fields['tag_field'], $mapped_tag_ids);
    }

    $thumb_uri = !empty($this->videoData->itemData->imagedata->thumb) ? $this->videoData->itemData->imagedata->thumb : '';
    if ($this->config('nexx_integration.settings')->get('bigger_thumbnail', 0)) {
      if ($this->videoData->itemData->imagedata->thumb_hasX3 == 1) {
        $thumb_uri = str_replace('xL', 'x3', $thumb_uri);
      }
      elseif ($this->videoData->itemData->imagedata->thumb_hasX2 == 1) {
        $thumb_uri = str_replace('xL', 'x2', $thumb_uri);
      }
    }

    if (!empty($fields['teaser_image_field']) && $media->$videoField->thumb !== $this->videoData->itemData->imagedata->thumb) {
      if (!empty($thumb_uri)) {
        $media->$videoField->thumb = $thumb_uri;
        $this->mapTeaserImage($media, $fields['teaser_image_field']);
      }
      else {
        $media->$videoField->thumb = '';
      }
    }

    // Media entity does not update mapped fields by itself.
    foreach ($media->bundle->entity->getFieldMap() as $destination_field) {
      if ($media->hasField($destination_field) && ($value = $media->getSource()->getSourceFieldValue($media))) {
        $media->set($destination_field, $value);
      }
    }
  }

  /**
   * Map incoming teaser image to medie entity field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   * @param mixed $teaserImageField
   *   The machine name of the field, that stores the file.
   *
   * @throws \Exception
   */
  protected function mapTeaserImage(MediaInterface $media, $teaserImageField) {
    $images_field = $media->$teaserImageField;
    if (empty($images_field)) {
      return;
    }
    $images_field_target_type = $images_field->getSetting('target_type');

    /*
     * TODO: there must be a better way to get this information,
     *       then creating a dummy object
     */
    $images_field_target_bundle = array_shift($images_field->getSetting('handler_settings')['target_bundles']);
    if (empty($images_field_target_bundle)) {
      throw new \Exception('No image field target bundle.');
    }
    $storage = $this->entityTypeManager()
      ->getStorage($images_field_target_type);

    /** @var \Drupal\media\MediaInterface $thumbnail_entity */
    $thumbnail_entity = $storage->create([
      'bundle' => $images_field_target_bundle,
      'name' => $media->label(),
    ]);
    $updated_thumbnail_entity = FALSE;

    if ($thumb_uri = $this->videoData->itemData->imagedata->thumb) {
      if ($this->config('nexx_integration.settings')->get('bigger_thumbnail', 0)) {
        if ($this->videoData->itemData->imagedata->thumb_hasX3 == 1) {
          $thumb_uri = str_replace('xL', 'x3', $thumb_uri);
        }
        elseif ($this->videoData->itemData->imagedata->thumb_hasX2 == 1) {
          $thumb_uri = str_replace('xL', 'x2', $thumb_uri);
        }
      }

      // Get configured source field from media entity type definition.
      $thumbnail_upload_field_definition = $thumbnail_entity->getSource()->getSourceFieldDefinition($thumbnail_entity->bundle->entity);
      $thumbnail_upload_field = $thumbnail_upload_field_definition->getName();
      // Get field settings from this field.
      $thumbnail_upload_field_settings = $thumbnail_upload_field_definition->getSettings();
      // Use file directory and uri_scheme out of these settings to create
      // destination directory for file upload.
      $upload_directory = $this->token->replace($thumbnail_upload_field_settings['file_directory']);
      $destination_file = $thumbnail_upload_field_settings['uri_scheme'] . '://' . $upload_directory . '/' . basename($thumb_uri);
      $destination_directory = dirname($destination_file);
      if ($destination_directory) {
        // Import file.
        file_prepare_directory($destination_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
        $thumbnail = file_save_data(file_get_contents($thumb_uri), $destination_file, FILE_EXISTS_REPLACE);
        // Add this file to thumbnail field of the nexx media entity.
        $thumbnail_entity->$thumbnail_upload_field->appendItem([
          'target_id' => $thumbnail->id(),
          'alt' => $media->label(),
        ]);
        $updated_thumbnail_entity = TRUE;
      }
    }
    // If new thumbnails were found,
    // safe the thumbnail media entity and link it to the nexx media entity.
    if ($updated_thumbnail_entity) {
      $thumbnail_entity->save();
      $media->$teaserImageField = ['target_id' => $thumbnail_entity->id()];
    }
  }

  /**
   * Map omnia term Id to corresponding drupal term id.
   *
   * @param int $omnia_id
   *   The omnia id of the term.
   * @param int $vid
   *   The drupal vid of the term.
   *
   * @return int
   *   The drupal id of the term.
   */
  protected function mapTermId($omnia_id, $vid) {
    $result = $this->database->select('nexx_taxonomy_term_data', 'n')
      ->fields('n', ['tid'])
      ->condition('n.nexx_item_id', $omnia_id)
      ->condition('n.vid', $vid)
      ->execute();

    $drupal_id = $result->fetchField();

    return $drupal_id;
  }

  /**
   * Map multiple omnia term ids to drupal term ids.
   *
   * @param int[] $omnia_ids
   *   Array of omnia termn ids.
   * @param int $vid
   *   The drupal vid of the term.
   *
   * @return int[]
   *   Array of mapped drupal ids, might contain less ids then the input array.
   */
  protected function mapMultipleTermIds(array $omnia_ids, $vid) {
    $drupal_ids = [];
    foreach ($omnia_ids as $omnia_id) {
      if (empty($omnia_id)) {
        continue;
      }

      $drupalId = $this->mapTermId($omnia_id, $vid);
      if ($drupalId) {
        $drupal_ids[] = $drupalId;
      }
      else {
        $this->logger->warning('Unknown omnia ID @term_id"',
          [
            '@term_id' => $omnia_id,
          ]
        );
      }
    }

    return $drupal_ids;
  }

  /**
   * Set proper publish state to media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   */
  protected function setState(MediaInterface $media) {

    $status = FALSE;

    if ($this->nexxVideoData['active'] == 1
      && $this->nexxVideoData['isSSC'] == 1
    ) {
      $status = TRUE;

      $this->logger->info('Published video "@title" (Drupal id: @id)',
        [
          '@title' => $this->nexxVideoData['title'],
          '@id' => $media->id(),
        ]
      );
    }
    else {
      $this->logger->info('Unpublished video "@title" (Drupal id: @id)... States: 
      active:@active 
      isSSC:@isSSC 
      validfrom_ssc:@validfrom_ssc 
      validto_ssc:@validto_ssc ',
        [
          '@title' => $this->nexxVideoData['title'],
          '@id' => $media->id(),
          '@active' => $this->nexxVideoData['active'],
          '@isSSC' => $this->nexxVideoData['isSSC'],
          '@validfrom_ssc' => $this->nexxVideoData['validfrom_ssc'],
          '@validto_ssc' => $this->nexxVideoData['validto_ssc'],
        ]
      );
    }

    $media->set("status", $status);
  }

}
