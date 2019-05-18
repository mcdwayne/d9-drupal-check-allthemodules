<?php

namespace Drupal\photoshelter;

use DateTime;
use DateTimeZone;
use Drupal\Core\Config\Config;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Exception;

/**
 * Class PhotoshelterService.
 *
 * @package Drupal\photoshelter
 */
class PhotoshelterService {

  /**
   * Photoshelter API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Photoshelter authenticate token.
   *
   * @var string
   */
  protected $token;

  /**
   * Photoshelter base url.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Photoshelter collections array.
   *
   * @var array
   */
  protected $rootCollections;

  /**
   * Photoshelter galleries array.
   *
   * @var array
   */
  protected $rootGalleries;

  /**
   * Photoshelter credentials array.
   *
   * @var array
   */
  protected $credentials;

  /**
   * Current time.
   *
   * @var DateTime
   */
  protected $currentTime;

  /**
   * PhotoShelter user timezone.
   *
   * @var string
   */
  protected $psTimezone;

  /**
   * Owner id for images.
   *
   * @var int
   */
  private $uid;

  /**
   * Allow private files status.
   *
   * @var bool
   */
  private $allowPrivate;

  /**
   * Maximum dimensions for images.
   *
   * @var int
   */
  private $maxDim;

  /**
   * Options array for curl request.
   *
   * @var array
   */
  private $curlOptions;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * PhotoshelterService constructor.
   *
   * @param MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    $this->curlOptions    = [
      CURLOPT_RETURNTRANSFER   => TRUE,
      CURLOPT_ENCODING         => "",
      CURLOPT_MAXREDIRS        => 10,
      CURLOPT_HTTP_VERSION     => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST    => "GET",
      CURLOPT_SSL_VERIFYPEER   => FALSE,
      CURLOPT_FOLLOWLOCATION
    ];
    if (defined('CURLOPT_SSL_VERIFYSTATUS')) {
      $this->curlOptions[CURLOPT_SSL_VERIFYSTATUS] = FALSE;
    }
    $this->uid = 1;
    $this->baseUrl = 'https://www.photoshelter.com/psapi/v3/mem/';
    $config = \Drupal::config('photoshelter.settings');
    $this->credentials = [
      'email' => $config->get('email'),
      'password' => $config->get('password'),
    ];
    $this->apiKey = $config->get('api_key');
    $this->allowPrivate = $config->get('allow_private');
    $this->maxDim = $config->get('max_width') . 'x' . $config->get('max_height');
    $this->rootCollections = $config->get('collections');
    $this->rootGalleries = $config->get('galleries');
    $this->currentTime = new DateTime(NULL, new DateTimeZone('GMT'));
    $this->authenticate();
  }

  /**
   * Send request to authenticate to the service and retrieve token.
   *
   * @return string
   *   Authentication token string.
   */
  public function authenticate() {
    $endpoint = 'authenticate';
    $fullUrl  = $this->baseUrl . $endpoint .
      '?api_key=' . $this->apiKey .
      '&email=' . $this->credentials['email'] .
      '&password=' . $this->credentials['password'] .
      '&mode=token';

    // cURL to /psapi/v3/mem/authenticate to see if credentials are valid.
    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, $this->curlOptions);
    $response = curl_exec($ch);
    if ($response === FALSE) {
      $this->messenger->addError(t('request error'));
      curl_close($ch);
    }
    else {
      curl_close($ch);
      $jsonResponse = json_decode($response, TRUE);
      if ($jsonResponse['status'] != 'ok') {
        $this->token = 'error';
      }
      else {
        $this->token = $jsonResponse['data']['token'];
        // Authenticate as an organization if needed.
        if (isset($jsonResponse['data']['org'][0]['id']) && !empty($jsonResponse['data']['org'][0]['id'])) {
          $org_id = $jsonResponse['data']['org'][0]['id'];
          $endpoint = 'organization/' . $org_id . '/authenticate';
          $fullUrl  = $this->baseUrl . $endpoint .
            '?api_key=' . $this->apiKey .
            '&auth_token=' . $this->token;
          $ch = curl_init($fullUrl);
          curl_setopt_array($ch, $this->curlOptions);
          curl_exec($ch);
          curl_close($ch);
        }

        // Get timezone.
        $endpoint = 'user/session';
        $fullUrl  = $this->baseUrl . $endpoint .
          '?api_key=' . $this->apiKey .
          '&auth_token=' . $this->token;
        $ch = curl_init($fullUrl);
        curl_setopt_array($ch, $this->curlOptions);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, TRUE);
        $this->psTimezone = $response['data']['Session']['tz'];
      }
    }
    return $this->token;
  }

  /**
   * Retrieve Photoshelter root containers names and id.
   *
   * @return array
   *   Container array with ids and names and type.
   */
  public function getContainersNames() {
    if ($this->token == 'error') {
      $this->messenger->addError(t('Invalid credentials'));
      return;
    }
    // Get collection data.
    $containers_array = [];
    if (!empty($this->token)) {
      $endpoint = 'collection/root/children';
      $fullUrl  = $this->baseUrl . $endpoint .
        '?api_key=' . $this->apiKey .
        '&auth_token=' . $this->token;

      $curl    = curl_init($fullUrl);
      curl_setopt_array($curl, $this->curlOptions);

      $response = curl_exec($curl);
      $err      = curl_error($curl);

      curl_close($curl);

      if ($err) {
        echo "cURL Error #:" . $err;
        exit(1);
      }

      $response    = json_decode($response, TRUE);
      $collections = $response['data']['Children']['Collection'];
      foreach ($collections as $collection) {
        if ($collection['mode'] == 'everyone' || $this->allowPrivate == TRUE) {
          $containers_array[] = [
            'name' => $collection['name'],
            'id' => $collection['collection_id'],
            'type' => 'collection',
          ];
        }
      }

      $galleries = $response['data']['Children']['Gallery'];
      foreach ($galleries as $gallery) {
        if ($gallery['mode'] == 'everyone' || $this->allowPrivate == TRUE) {
          $containers_array[] = [
            'name' => $gallery['name'],
            'id' => $gallery['gallery_id'],
            'type' => 'gallery',
          ];
        }
      }
    }
    return $containers_array;
  }

  /**
   * Queuing for synchronization if automatic sync is set in config form.
   */
  public function queueSyncNew() {
    if ($this->token == 'error') {
      \Drupal::logger('photoshelter')->error(t('Invalid credentials'));
      return;
    }
    $config = \Drupal::service('config.factory')->getEditable('photoshelter.settings');
    $automaticSync = $config->get('cron_sync');
    if ($automaticSync) {
      $time   = $config->get('last_sync');
      // Get the date.
      if ($time === 'Never') {
        try {
          $time = new DateTime('1970-01-01', new DateTimeZone('GMT'));
        }
        catch (Exception $e) {
          echo $e->getMessage();
          exit(1);
        }
      }
      else {
        $time = DateTime::createFromFormat(DateTime::RFC850, $time,
          new DateTimeZone('GMT'));
      }
      $next_sync_time = clone $time;
      $next_sync_time->modify('+1 day');
      $current_hour = $this->currentTime->format('G');

      // We check if it's been a day since last queuing
      // and if current time is between 0 and 4 am.
      if ($next_sync_time < $this->currentTime && ($current_hour < 4 && $current_hour > 0)) {
        // Update time saved in config.
        $this->updateConfigPostSync($config);

        $update = TRUE;
        $queueGalleries = [];
        $root_galleries = $this->rootGalleries;
        foreach ($root_galleries as $gallery_id) {
          if ($gallery_id != '0') {
            $queueGalleries[] = [
              'gallery_id' => $gallery_id,
              'time' => $time,
              'update' => $update,
              'parentId' => NULL,
            ];
          }
        }

        $queueCollections = [];
        $root_collections = $this->rootCollections;
        foreach ($root_collections as $collection_id) {
          if ($collection_id != '0') {
            $queueCollections[] = [
              'collection_id' => $collection_id,
              'time' => $time,
              'update' => $update,
              'parentId' => NULL,
            ];
          }
        }

        if (empty($queueCollections) && empty($queueGalleries)) {
          \Drupal::logger('photoshelter')->notice(t('No new data to synchronize on photoshelter'));
        }
        else {
          $queue_factory = \Drupal::service('queue');
          if (!empty($queueGalleries)) {
            \Drupal::logger('photoshelter')->notice(t('PhotoShelter queueing of galleries for synchronization'));
            $queue = $queue_factory->get('photoshelter_syncnew_gallery');
            $queue->createQueue();
            foreach ($queueGalleries as $queueItem) {
              $queue->createItem($queueItem);
            }
          }
          if (!empty($queueCollections)) {
            \Drupal::logger('photoshelter')->notice(t('PhotoShelter queueing of collections for synchronization'));
            $queue = $queue_factory->get('photoshelter_syncnew_collection');
            $queue->createQueue();
            foreach ($queueCollections as $queueItem) {
              $queue->createItem($queueItem);
            }
          }
        }
      }
    }
  }

  /**
   * Get data to synchronize in a batch.
   *
   * @param DateTime $time
   *   Date to compare with for update.
   * @param bool $update
   *   If update or full sync.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getData(DateTime $time, $update = FALSE) {
    if ($this->token == 'error') {
      $this->messenger->addError(t('Invalid credentials'));
      return;
    }
    \Drupal::logger('photoshelter')->notice(t('Start photoshelter synchronization'));

    $operations = [];

    $root_galleries = $this->rootGalleries;
    foreach ($root_galleries as $gallery_id) {
      if ($gallery_id != '0') {
        $operations[] = [
          'photoshelter_sync_gallery',
          [$gallery_id, $time, $update, NULL],
        ];
      }
    }

    $root_collections = $this->rootCollections;
    foreach ($root_collections as $collection_id) {
      if ($collection_id != '0') {
        $operations[] = [
          'photoshelter_sync_collection',
          [$collection_id, $time, $update, NULL],
        ];
      }
    }

    $batch = array(
      'title' => t('PhotoShelter synchronization'),
      'operations' => $operations,
      'finished' => 'photoshelter_sync_finished',
      'file' => drupal_get_path('module', 'photoshelter') . '/photoshelter.batch.inc',
    );

    batch_set($batch);
  }

  /**
   * Send request to get one collection and get results.
   *
   * @param string $collection_id
   *   The collection PhotoShelter ID.
   * @param DateTime $time
   *   Date to compare with for update.
   * @param bool $update
   *   If update or full sync.
   * @param string $process
   *   Type of process (batch or queue).
   * @param string|null $parentId
   *   Parent collection ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getCollection($collection_id, DateTime $time, $update, $process, $parentId = NULL) {
    $endpoint = 'collection/' . $collection_id;
    $extend = [
      'KeyImage' => [
        'fields' => 'image_id',
        'params' => [],
      ],
      'ImageLink' => [
        'fields' => 'link',
        'params' => [
          'image_size' => $this->maxDim,
          'f_https_link' => 't',
        ],
      ],
      'Visibility' => [
        'fields' => 'mode',
        'params' => [],
      ],
      'Children' => [
        'Gallery' => [
          'fields' => 'gallery_id',
          'params' => [],
        ],
        'Collection' => [
          'fields' => 'collection_id',
          'params' => [],
        ],
      ],
    ];
    $extend_json = json_encode($extend);
    $fullUrl  = $this->baseUrl . $endpoint .
      '?fields=collection_id,name,description,effective_mode,modified_at' .
      '&api_key=' . $this->apiKey .
      '&auth_token=' . $this->token .
      "&extend=" . $extend_json;

    $curl    = curl_init($fullUrl);
    curl_setopt_array($curl, $this->curlOptions);

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    }

    $jsonResponse = json_decode($response, TRUE);
    $collection   = $jsonResponse['data']['Collection'];

    if ($collection['Visibility']['mode'] == 'everyone' || $this->allowPrivate == TRUE) {
      $this->saveCollection($collection, $time, $update, $collection['Visibility']['mode'], $process, $parentId);
    }
  }

  /**
   * Send request to get one gallery and get results.
   *
   * @param string $gallery_id
   *   The gallery PhotoShelter ID.
   * @param DateTime $time
   *   Date to compare with for update.
   * @param bool $update
   *   If update or full sync.
   * @param string $process
   *   Type of process (batch or queue).
   * @param string|null $parentTermId
   *   Parent container term ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getGallery($gallery_id, DateTime $time, $update, $process, $parentTermId = NULL) {

    $endpoint = 'gallery/' . $gallery_id;
    $extend = [
      'KeyImage' => [
        'fields' => 'image_id',
        'params' => [],
      ],
      'ImageLink' => [
        'fields' => 'link',
        'params' => [
          'image_size' => $this->maxDim,
          'f_https_link' => 't',
        ],
      ],
      'Visibility' => [
        'fields' => 'mode',
        'params' => [],
      ],
    ];
    $extend_json = json_encode($extend);
    $fullUrl  = $this->baseUrl . $endpoint .
      '?fields=gallery_id,name,description,modified_at' .
      '&api_key=' . $this->apiKey .
      '&auth_token=' . $this->token .
      "&extend=" . $extend_json;

    $curl    = curl_init($fullUrl);
    curl_setopt_array($curl, $this->curlOptions);

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    }

    $jsonResponse = json_decode($response, TRUE);
    $gallery   = $jsonResponse['data']['Gallery'];
    if ($gallery['Visibility']['mode'] == 'everyone' || $this->allowPrivate == TRUE) {
      $this->saveGallery($gallery, $time, $update, $gallery['Visibility']['mode'], $process, $parentTermId);
    }
  }

  /**
   * Save one collection data and add children to batch or queue.
   *
   * @param array $collection
   *   The collection data array.
   * @param DateTime $time
   *   Date to compare with for update.
   * @param bool $update
   *   If update or full sync.
   * @param string $collectionVisibility
   *   Collection visibility.
   * @param string $process
   *   Type of process (batch or queue).
   * @param string|null $parentId
   *   Parent collection ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveCollection(array $collection, DateTime $time, $update, $collectionVisibility, $process, $parentId = NULL) {
    $collectionId   = $collection['collection_id'];
    $collectionName = $collection['name'];
    $cModified      = $collection['modified_at'];
    $cDescription   = $collection['description'];
    $cKeyImage      = $collection['KeyImage']['image_id'];
    $cChildren      = $collection['Children'];
    $cKeyImageFile  = $collection['KeyImage']['ImageLink']['link'];
    unset($collection);

    $collectionTime = new DrupalDateTime($cModified, $this->psTimezone);

    if ($cKeyImageFile !== NULL) {
      $file = File::create(['uri' => $cKeyImageFile]);
      $file->save();
    }

    // If already exist, update if needed.
    $collection_id = $this->containerExists($collectionId);
    if (!empty($collection_id)) {
      $term = Term::load($collection_id);
      $last_sync_time = $term->get('field_ps_last_sync_date');
      if ($update &&  $collectionTime < $last_sync_time) {
        // The collection has not been modified, no update needed.
      }
      else {
        $term->set('name', $collectionName);
        $term->set('description', $cDescription);
        $term->set('field_ps_permission', $collectionVisibility);
        $term->set('parent', isset($parentId) ? ['target_id' => $this->getParentTerm($parentId)] : NULL);
        $term->set('field_ps_modified_at', $cModified);
        $term->set('field_ps_key_image_id', $cKeyImage);
        $term->set('field_ps_key_image', isset($file) ? ['target_id' => $file->id()] : NULL);
        $term->set('field_ps_last_sync_date', $this->currentTime->format(DATETIME_DATETIME_STORAGE_FORMAT));
        $term->save();
      }
    }
    else {
      // If new collection force synchronization of children.
      $update = FALSE;
      // Create term from $collection.
      $term = Term::create([
        'langcode'             => 'en',
        'vid'                 => 'ps_container',
        'name'           => $collectionName,
        'description'    => $cDescription,
        'field_ps_permission'   => $collectionVisibility,
        'field_ps_id'             => $collectionId,
        'parent' => isset($parentId) ? ['target_id' => $this->getParentTerm($parentId)] : NULL,
        'field_ps_modified_at' => $cModified,
        'field_ps_key_image_id'   => $cKeyImage,
        'field_ps_key_image' => isset($file) ?
          ['target_id' => $file->id()] : NULL,
        'field_ps_last_sync_date' => $this->currentTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
      ]);
      $term->save();
    }

    $termId = $term->id();
    $queueGalleries = [];
    $queueCollections = [];
    // Create terms for children.
    if (isset($cChildren)) {
      foreach ($cChildren as $child) {
        switch (key($cChildren)) {
          case 'Gallery':
            foreach ($child as $gallery) {
              // Add condition here to not process if not update
              // check if gallery exist if so load it and
              // then check if needs update.
              if ($process == 'batch') {
                $operations[] = [
                  'photoshelter_sync_gallery',
                  [$gallery['gallery_id'], $time, $update, $termId],
                ];
              }
              elseif ($process == 'queue') {
                $queueGalleries[] = [
                  'gallery_id' => $gallery['gallery_id'],
                  'time' => $time,
                  'update' => $update,
                  'parentId' => $termId,
                ];
              }
              unset($gallery);
            }
            break;

          case 'Collection':
            foreach ($child as $childCollection) {
              if ($process == 'batch') {
                $operations[] = [
                  'photoshelter_sync_collection',
                  [
                    $childCollection['collection_id'],
                    $time,
                    $update,
                    $collectionId,
                  ],
                ];
              }
              elseif ($process == 'queue') {
                $queueCollections[] = [
                  'collection_id' => $childCollection['collection_id'],
                  'time' => $time,
                  'update' => $update,
                  'parentId' => $collectionId,
                ];
              }

              unset($childCollection);
            }
            unset($collection);
            break;
        }
        unset($child);
        next($cChildren);
      }

      if (!empty($operations)) {
        $batch = array(
          'title' => t('Synchronization of ') . $collectionName,
          'operations' => $operations,
          'finished' => 'photoshelter_sync_finished',
          'file' => drupal_get_path('module', 'photoshelter') . '/photoshelter.batch.inc',
        );

        batch_set($batch);
      }

      if (empty($queueCollections) && empty($queueGalleries)) {
        // No children.
      }
      else {
        $queue_factory = \Drupal::service('queue');
        if (!empty($queueGalleries)) {
          \Drupal::logger('photoshelter')->notice(t('PhotoShelter queueing of galleries for synchronization'));
          $queue = $queue_factory->get('photoshelter_syncnew_gallery');
          $queue->createQueue();
          foreach ($queueGalleries as $queueItem) {
            $queue->createItem($queueItem);
          }
        }
        if (!empty($queueCollections)) {
          \Drupal::logger('photoshelter')->notice(t('PhotoShelter queueing of collections for synchronization'));
          $queue = $queue_factory->get('photoshelter_syncnew_collection');
          $queue->createQueue();
          foreach ($queueCollections as $queueItem) {
            $queue->createItem($queueItem);
          }
        }
      }
    }
  }

  /**
   * Save gallery term and trigger getPhotos.
   *
   * @param array $gallery
   *   Gallery data array.
   * @param DateTime $time
   *   Date to compare with for update.
   * @param bool $update
   *   If update or full sync.
   * @param string $galleryVisibility
   *   The gallery visibility.
   * @param string $process
   *   Type of process (batch or queue).
   * @param string|null $parentTermId
   *   Parent container term ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveGallery(array $gallery, DateTime $time, $update, $galleryVisibility, $process, $parentTermId = NULL) {
    $galleryId          = $gallery['gallery_id'];
    $galleryModified    = $gallery['modified_at'];
    $galleryName        = $gallery['name'];
    $galleryDescription = $gallery['description'];
    $galleryImage       = $gallery['KeyImage']['image_id'];
    $galleryImageFile   = $gallery['KeyImage']['ImageLink']['link'];
    unset($gallery);

    $galleryTime = new DrupalDateTime($galleryModified, $this->psTimezone);

    if (isset($galleryImageFile)) {
      $file = File::create(['uri' => $galleryImageFile]);
      $file->save();
    }

    // If already exists, update instead of create.
    $gallery_id = $this->containerExists($galleryId);
    if (!empty($gallery_id)) {
      $term = Term::load($gallery_id);
      $last_sync_time = $term->get('field_ps_last_sync_date')->getString();
      $last_sync_time_obj = new DrupalDateTime($last_sync_time, DATETIME_STORAGE_TIMEZONE);
      if ($galleryTime < $last_sync_time_obj) {
        // The collection has not been modified, no update needed.
      }
      else {
        $term->set('name', $galleryName);
        $term->set('description', $galleryDescription);
        $term->set('field_ps_permission', $galleryVisibility);
        $term->set('parent', isset($parentTermId) ? ['target_id' => $parentTermId] : NULL);
        $term->set('field_ps_modified_at', $galleryModified);
        $term->set('field_ps_key_image_id', $galleryImage);
        $term->set('field_ps_key_image', isset($file) ? ['target_id' => $file->id()] : NULL);
        $term->set('field_ps_last_sync_date', $this->currentTime->format(DATETIME_DATETIME_STORAGE_FORMAT));
        $term->set('field_ps_sync_complete', 0);
        $term->save();
      }
    }
    else {
      // If new gallery force synchronization of children.
      $update = FALSE;
      $term = Term::create([
        'langcode'             => 'en',
        'vid'                 => 'ps_container',
        'name'           => $galleryName,
        'description'    => $galleryDescription,
        'field_ps_permission'   => $galleryVisibility ,
        'field_ps_id'             => $galleryId,
        'parent' => isset($parentTermId) ? ['target_id' => $parentTermId] : NULL,
        'field_ps_modified_at' => $galleryModified,
        'field_ps_key_image_id'   => $galleryImage,
        'field_ps_key_image' => isset($file) ? ['target_id' => $file->id()] : NULL,
        'field_ps_last_sync_date' => $this->currentTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'field_ps_sync_complete' => 0,
      ]);
      $term->save();
    }
    $is_complete = $term->get('field_ps_sync_complete')->getString();
    $process = 'queue';
    if (!isset($last_sync_time_obj) || ($galleryTime < $last_sync_time_obj && $is_complete == 0)) {
      $this->getPhotos($galleryId, $galleryVisibility, $time, $process, $update);
    }
  }

  /**
   * Get Photo list and set synchronization process.
   *
   * @param string $parentId
   *   The parent gallery Id.
   * @param string $parentVisibility
   *   The parent visibility.
   * @param \DateTime $time
   *   Date to compare with for update.
   * @param string $process
   *   Type of process (batch or queue).
   * @param bool $update
   *   If update or full sync.
   */
  public function getPhotos($parentId, $parentVisibility, DateTime $time, $process, $update) {
    if ($process == 'queue') {
      \Drupal::logger('photoshelter')->notice(t('Start photoshelter queueing of photo for synchronization'));
      $queue_factory = \Drupal::service('queue');
      $queue = $queue_factory->get('photoshelter_syncnew_photo');
      $queue->createQueue();
    }
    $endpoint = 'gallery/' . $parentId . '/images';
    $extend = [
      'Image' => [
        'fields' => 'image_id,file_name,updated_at',
        'params' => [],
      ],
      'ImageLink' => [
        'fields' => 'link,auth_link',
        'params' => [
          'image_size' => $this->maxDim,
          'f_https_link' => 't',
        ],
      ],
      'Iptc' => [
        'fields' => 'keyword,credit,caption,copyright',
        'params' => [],
      ],
    ];
    $extend_json = json_encode($extend);
    $page = 1;
    do {
      // Get list of images in gallery.
      $fullUrl  = $this->baseUrl . $endpoint .
        '?fields=image_id,f_visible' .
        '&api_key=' . $this->apiKey .
        '&auth_token=' . $this->token .
        '&per_page=750' .
        '&page=' . $page .
        "&extend=" . $extend_json;

      $curl    = curl_init($fullUrl);curl_setopt_array($curl, $this->curlOptions);
      $response = curl_exec($curl);
      $err      = curl_error($curl);
      curl_close($curl);
      if ($err) {
        echo "cURL Error #:" . $err;
        exit(1);
      }
      $response = json_decode($response, TRUE);
      if ($response['status'] != 'ok') {
        $this->messenger->addError(t('authentication problem.'));
        exit(1);
      }
      $images   = $response['data']['GalleryImage'];
      $paging   = $response['data']['Paging'];
      if (!empty($paging) && !array_key_exists('next', $paging)) {
        $page = 0;
      }
      unset($paging);
      unset($response);

      $parentTermId = $this->getParentTerm($parentId);

      // Cycle through all images.
      $operations = [];
      foreach ($images as $image) {
        $image['Image']['parentTermId'] = $parentTermId;
        if ($process == 'batch') {
          $operations[] = [
            'photoshelter_sync_photo',
            [$image, $parentVisibility],
          ];
        }
        elseif ($process == 'queue') {
          $data = [
            'image' => $image,
            '$parentVisibility' => $parentVisibility,
          ];
          $queue->createItem($data);
        }
      }

      if ($page !== 0) {
        $page++;
      }
    } while ($page !== 0);

    if ($process == 'batch') {
      $batch = array(
        'title' => t('Photos import'),
        'operations' => $operations,
        'finished' => 'photoshelter_sync_photo_finished',
        'file' => drupal_get_path('module', 'photoshelter') . '/photoshelter.batch.inc',
      );

      batch_set($batch);
    }
    elseif ($process == 'queue') {
      $term = Term::load($parentTermId);
      $term->set('field_ps_sync_complete', 1);
      $term->save();
      \Drupal::logger('photoshelter')->notice(t('Queuing complete for photo import of gallery:') . $term->getName());
    }
  }

  /**
   * Save one photo.
   *
   * @param array $image
   *   Image data array.
   * @param string $parentVisibility
   *   Parent gallery visibility.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getPhoto(array $image, $parentVisibility) {

    $imageUpdate   = $image['Image']['updated_at'];
    $imageId       = $image['image_id'];
    $imageName     = $image['Image']['file_name'];
    $imageKeywords = $image['Image']['Iptc']['keyword'];
    $imageLink     = $image['ImageLink']['link'];
    $imageCaption  = $image['Image']['Iptc']['caption'];
    $imageCredit   = $image['Image']['Iptc']['credit'];
    $imageCopyright = $image['Image']['Iptc']['copyright'];
    $parentTermId  = $image['Image']['parentTermId'];
    unset($image);
    if (isset($imageLink)) {
      $file = File::create([
        'uri' => $imageLink,
        'alt' => $imageName,
      ]);
      $file->save();
    }

    // If already exists, update instead of create.
    $media_id = $this->imageExists($imageId);
    if (!empty($media_id)) {
      $media = Media::load($media_id);
      $media->set('name', $imageName);
      $media->set('field_ps_permission', $parentVisibility);
      $media->set('field_ps_parent_container', ['target_id' => $parentTermId]);
      $media->set('field_ps_modified_at', $imageUpdate);
      $media->set('field_ps_caption', $imageCaption);
      $media->set('field_ps_credit', $imageCredit);
      $media->set('field_ps_copyright', $imageCopyright);
      $media->set('field_media_image', isset($file) ? [
        'target_id' => $file->id(),
        'alt' => $imageName,
      ] : NULL);

    }
    else {
      // Create media entity from $image.
      $media = Media::create([
        'langcode'             => 'en',
        'uid'                  => $this->uid,
        'bundle'                 => 'ps_image',
        'name'                => $imageName,
        'status'               => 1,
        'created'              => \Drupal::time()->getRequestTime(),
        'field_ps_permission'   => $parentVisibility,
        'field_ps_id'             => $imageId,
        'field_ps_parent_container' => ['target_id' => $parentTermId],
        'field_ps_modified_at' => $imageUpdate,
        'field_ps_caption'        => $imageCaption,
        'field_ps_credit'         => $imageCredit,
        'field_ps_copyright' => $imageCopyright,
        'field_media_image' => isset($file) ? [
          'target_id' => $file->id(),
          'alt' => $imageName,
        ] : NULL,
      ]);
    }
    $terms = [];
    if (isset($imageKeywords) && !empty($imageKeywords)) {
      $taxonomy = explode(',', $imageKeywords);
      foreach ($taxonomy as $term) {
        $term = trim($term);
        $termId = $this->termExists($term, 'ps_tags');
        if ($termId === 0) {
          $keyword = Term::create([
            'name' => $term,
            'vid'  => 'ps_tags',
          ]);
          $keyword->save();
          $terms[] = ['target_id' => $keyword->id()];
        }
        else {
          $terms[] = ['target_id' => $termId];
        }
      }
    }

    $media->set('field_ps_tags', $terms);

    try {
      $media->save();
    }
    catch (Exception $e) {
      echo $e->getMessage();
      exit(1);
    }
    if (isset($file)) {
      unset($file);
    }
    unset($media);
  }

  /**
   * Get the parent term id.
   *
   * @param string $parent_ps_id
   *   Photoshelter id of the parent collection or gallery.
   *
   * @return string
   *   The parent term id.
   */
  public function getParentTerm($parent_ps_id) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('field_ps_id', $parent_ps_id);
    $tids = $query->execute();
    $tid = !empty($tids) ? reset($tids) : '';
    return $tid;
  }

  /**
   * Check if container term exist.
   *
   * @param string $container_ps_id
   *   Photoshelter id of the container.
   *
   * @return string
   *   Taxonomy term id.
   */
  private function containerExists($container_ps_id) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'ps_container');
    $query->condition('field_ps_id', $container_ps_id);
    $tids = $query->execute();
    $tid = !empty($tids) ? reset($tids) : '';
    return $tid;
  }

  /**
   * Check if media entity exist.
   *
   * @param string $image_ps_id
   *   Photoshelter id of the image.
   *
   * @return string
   *   Media id.
   */
  private function imageExists($image_ps_id) {
    $query = \Drupal::entityQuery('media');
    $query->condition('bundle', 'ps_image');
    $query->condition('field_ps_id', $image_ps_id);
    $mids = $query->execute();
    $mid = !empty($mids) ? reset($mids) : '';
    return $mid;
  }

  /**
   * Check by name if PS tag term exist.
   *
   * @param string|null $name
   *   The term name.
   * @param string|null $vid
   *   The term vocabulary.
   *
   * @return bool
   *   True or False.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function termExists($name = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term->id() : 0;
  }

  /**
   * Update the photoshelter config last synchronization date.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object.
   */
  public function updateConfigPostSync(Config &$config) {
    $config->set('last_sync', $this->currentTime->format(
      DateTime::RFC850));
    $config->save();
  }

}
