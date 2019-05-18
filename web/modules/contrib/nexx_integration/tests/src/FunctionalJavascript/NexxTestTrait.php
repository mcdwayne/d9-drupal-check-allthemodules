<?php

namespace Drupal\Tests\nexx_integration\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Use this trait to reuse an existing database.
 */
trait NexxTestTrait {

  /**
   * Vocabularies used for testing.
   *
   * @var array
   */
  public static $testVocabularies = ['testChannel', 'testActor', 'testTags'];

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The nexx configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The admin user.
   *
   * @var Drupal\user\Entity\UserInterface
   */
  protected $adminUser;

  /**
   * The video edit user.
   *
   * @var Drupal\user\Entity\UserInterface
   */
  protected $videoUser;

  /**
   * A list of vocabularies keyed by name.
   *
   * @var array
   */
  protected $vocabularies;

  /**
   * List of lists of terms keyed by vocabulary name.
   *
   * The structure of this is:
   * [
   *  [vocabulary1] => [term1, term2]
   *  [vocabulary2] => [term1, term2]
   * ]
   *
   * @var array
   */
  protected $terms;

  /**
   * The field entity storage definition.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The video manager service.
   *
   * @var \Drupal\nexx_integration\VideoManagerServiceInterface
   */
  protected $videoManager;

  /**
   * The field entity definition.
   *
   * @var array
   */
  protected $fieldDefinition;

  /**
   * The drupal cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->database = $this->container->get('database');
    $this->videoManager = $this->container->get(
      'nexx_integration.videomanager'
    );
    $this->cron = $this->container->get('cron');

    // Prepare some users.
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
    $this->videoUser = $this->drupalCreateUser(
      ['use omnia notification gateway']
    );
    $this->setUpTaxonomy();
    $this->setTestConfig();
    $this->config = $this->config('nexx_integration.settings');
    $this->htmlOutputEnabled = FALSE;
  }

  /**
   * Create test data string.
   *
   * @param int $videoId
   *   Setup video ID.
   *
   * @return \stdClass
   *   Test video data object
   */
  protected function getTestVideoData($videoId) {

    $tags = [];
    foreach ($this->terms['testTags'] as $tag) {
      $tags[] = $this->mapOmniaTermId($tag->id(), 'tags');
    }
    $actors = [];
    foreach ($this->terms['testActor'] as $actor) {
      $actors[] = $this->mapOmniaTermId($actor->id(), 'star');
    }
    $channel = $this->mapOmniaTermId($this->terms['testChannel'][0]->id(), 'channel');

    $itemData = new \stdClass();
    $itemData->general = new \stdClass();
    $itemData->publishingdata = new \stdClass();
    $itemData->imagedata = new \stdClass();
    $itemData->channeldata = new \stdClass();

    $itemData->general->ID = $videoId;
    $itemData->general->hash = "GL7ADZXZJ" . $videoId . "P";
    $itemData->general->title = "Test Video $videoId";
    $itemData->general->alttitle = "Test Video $videoId Second Title";
    $itemData->general->subtitle = "Test Video $videoId SubTitle";
    $itemData->general->teaser = "The teaser text.";
    $itemData->general->description = "The description text $videoId";
    $itemData->general->altdescription = "The alternative description text $videoId";
    $itemData->general->uploaded = 1463997938;
    $itemData->general->copyright = "Copyright notice";
    $itemData->publishingdata->isEncoded = "1";
    $itemData->imagedata->thumb = "http://nx-i.akamaized.net/201605/G750452J1M6XAOWxL.jpg";
    $itemData->general->runtime = "00:02:45";
    $itemData->channeldata->ID = $channel;
    $itemData->general->persons_raw = implode(',', $actors);
    $itemData->general->tags_raw = implode(',', $tags);

    $itemData->publishingdata->allowedOnDesktop = 1;
    $itemData->publishingdata->validFromDesktop = 0;
    $itemData->publishingdata->validUntilDesktop = 0;
    $itemData->publishingdata->allowedOnSmartTV = 1;
    $itemData->publishingdata->validFromSmartTV = 0;
    $itemData->publishingdata->validUntilSmartTV = 0;
    $itemData->publishingdata->allowedOnMobile = 1;
    $itemData->publishingdata->validFromMobile = 0;
    $itemData->publishingdata->validUntilMobile = 0;
    $itemData->publishingdata->isEncoded = 1;
    $itemData->publishingdata->isPublished = 1;
    $itemData->publishingdata->isDeleted = 0;
    $itemData->publishingdata->isBlocked = 0;

    $baseData = new \stdClass();
    $baseData->itemID = $videoId;
    $baseData->itemReference = "";
    $baseData->itemMime = "video";
    $baseData->clientID = "1";
    $baseData->triggerReason = "metadata";
    $baseData->triggerTime = "1465392767";
    $baseData->sendingTime = 1465392783;
    $baseData->triggeredInSession = "214653913620510632";
    $baseData->triggeredByUser = "119574";
    $baseData->itemData = $itemData;

    return $baseData;
  }

  /**
   * Create test data delete string.
   *
   * @param int $videoId
   *   Setup video ID.
   *
   * @return \stdClass
   *   Test video data object
   */
  protected function getTestVideoDeleteData($videoId) {
    $baseData = new \stdClass();
    $baseData->itemID = $videoId;
    $baseData->itemReference = "";
    $baseData->itemMime = "video";
    $baseData->itemstreamtype = 'video';
    $baseData->clientID = "1";
    $baseData->triggerReason = "delete";
    $baseData->triggerTime = "1465392767";
    $baseData->sendingTime = 1465392783;
    $baseData->triggeredInSession = "214653913620510632";
    $baseData->triggeredByUser = "119574";
    $baseData->itemData = NULL;

    return $baseData;
  }

  /**
   * Load a video media entity.
   *
   * @param int $videoId
   *   The entity ID.
   *
   * @return EntityInterface
   *   The video entity.
   */
  protected function loadVideoEntity($videoId) {
    /** @var EntityTypeManager $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');

    /** @var EntityStorageInterface $mediaStorage */
    $mediaStorage = $entityTypeManager->getStorage('media');
    $mediaStorage->resetCache([$videoId]);
    return $mediaStorage->load($videoId);
  }

  /**
   * Post video data to endpoint.
   *
   * If no data is given, test data will be send.
   *
   * @param object $data
   *   Data to send at video endpoint.
   *
   * @return mixed
   *   Response Body of request.
   */
  protected function postVideoData($data) {
    $omniaUrl = Url::fromRoute('nexx_integration.omnia_notification_gateway', ['token' => $this->config->get('notification_access_key')], ['absolute' => TRUE]);
    $httpClient = $this->container->get('http_client');

    /* @var $response \GuzzleHttp\Psr7\Response */
    $response = $httpClient->post($omniaUrl->toString(), [
      'body' => json_encode($data),
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);

    $responseBody = \GuzzleHttp\json_decode($response->getBody()->getContents());
    return $responseBody;
  }

  /**
   * Map drupal term Id to corresponding omnia term id.
   *
   * @param int $tid
   *   The term id of the term.
   * @param int $vid
   *   The drupal vid of the term.
   *
   * @return int
   *   The omnia id of the term.
   */
  protected function mapOmniaTermId($tid, $vid) {
    $result = $this->database->select('nexx_taxonomy_term_data', 'n')
      ->fields('n', ['nexx_item_id'])
      ->condition('n.tid', $tid)
      ->condition('n.vid', $vid)
      ->execute();

    $drupal_id = $result->fetchField();

    return $drupal_id;
  }

  /**
   * Configure nexx settings.
   */
  protected function setTestConfig() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('nexx_integration.admin_settings'));
    $page = $this->getSession()->getPage();
    $page->fillField('edit-omnia-id', '1');
    $page->fillField('edit-notification-access-key', 'test-access-key');
    $page->pressButton('edit-submit');
  }

  /**
   * Setup taxonomy vocabularies and terms.
   */
  protected function setUpTaxonomy() {
    // Create vocabularies for channel, actor and tags.
    $this->vocabularies = [];

    foreach (self::$testVocabularies as $vocabularyName) {
      $this->vocabularies[$vocabularyName] = Vocabulary::create(
        ['vid' => $vocabularyName]
      );
      $this->vocabularies[$vocabularyName]->save();
      $this->terms[$vocabularyName] = [];

      // Populate the vocabulary with two terms.
      for ($i = 0; $i < 2; $i++) {
        $term = Term::create(
          [
            'name' => $vocabularyName . ' Term ' . $i,
            'vid' => $vocabularyName,
          ]
        );
        $term->save();
        $this->terms[$vocabularyName][$i] = $term;

        // Mapping an omnia ID.
        update_nexx_term_id_mapping(
          $term->id(),
          ($term->id() + 100),
          $vocabularyName
        );
      }
    }
  }

  /**
   * Load a video media entity.
   *
   * @return int
   *   Number of videos.
   */
  protected function countVideos() {
    /** @var EntityTypeManager $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');

    /** @var EntityStorageInterface $mediaStorage */
    $mediaStorage = $entityTypeManager->getStorage('media');
    $mediaStorage->resetCache();
    return count($mediaStorage->loadMultiple());
  }

}
