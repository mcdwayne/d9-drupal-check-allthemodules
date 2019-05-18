<?php

/**
 * Copyright 2017 Brigham Young University
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @file
 * Contains Drupal\byu_news_consumer\Form\ByuNewsConsumerConfigForm
 */

namespace Drupal\byu_news_consumer\Form;

use DateTime;
use DateTimeZone;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ByuNewsConsumerConfigForm
 *
 * @package Drupal\byu_news_consumer\Form
 */
class ByuNewsConsumerConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  protected $user;

  private $cookie;

  private $options;

  private $uid;

  /**
   * ByuNewsConsumerConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Connection $connection
  ) {
    parent::__construct($config_factory);
    $this->cookie     = dirname(__FILE__) . '/cookie.txt';
    $this->options    = [
      CURLOPT_RETURNTRANSFER   => TRUE,
      CURLOPT_ENCODING         => "",
      CURLOPT_MAXREDIRS        => 10,
      CURLOPT_HTTP_VERSION     => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST    => "GET",
      CURLOPT_COOKIEFILE       => $this->cookie,
      CURLOPT_COOKIEJAR        => $this->cookie,
      CURLOPT_SSL_VERIFYSTATUS => FALSE,
      CURLOPT_SSL_VERIFYPEER   => FALSE,
      CURLOPT_FOLLOWLOCATION
    ];
    $this->connection = $connection;
    $this->uid        = $this->currentUser()->id();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('config.factory'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'byu_news_consumer_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form              = parent::buildForm($form, $form_state);
    $config            = $this->config('byu_news_consumer.settings');
    $form['tag_ids']   = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Tag ID\'s separated by \'+\'.'),
      '#default_value' => $config->get('tag_ids'),
    ];
    $form['sync_new']  = [
      '#type'  => 'submit',
      '#value' => t('Sync New Additions'),
    ];
    $form['sync_full'] = [
      '#type'  => 'submit',
      '#value' => 'Sync All Data',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $op = $form_state->getValue('op');
    switch ($op) {
      case 'Save configuration':
        $this->submitForm($form, $form_state);
        break;
      case 'Sync All Data':
        $this->sync_full_submit($form, $form_state);
        break;
      case 'Sync New Additions':
        $this->sync_new_submit($form, $form_state, TRUE);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('byu_news_consumer.settings');
    $config->set('tag_ids', $form_state->getValue('tag_ids'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return ['byu_news_consumer.settings'];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function sync_full_submit(
    array &$form,
    FormStateInterface $form_state
  ) {
    $config = $this->config('byu_news_consumer.settings');

    $time = new DateTime(19700101);

    //Get the data
    $this->getData($time, $config);

    // Update time saved in config
    $this->updateConfigPostSync($config, TRUE);
    parent::submitForm($form, $form_state);
  }

  /**
   * @param bool $update
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function sync_new_submit(
    array &$form,
    FormStateInterface $form_state, bool $update = FALSE
  ) {
    $config = $this->config('byu_news_consumer.settings');
    $time   = $config->get('last_sync');

    // Get the date
    if ($time === 'Never') {
      try {
        $time = new DateTime(NULL, new DateTimeZone('GMT'));
      } catch (Exception $e) {
        echo $e->getMessage();
        exit(1);
      }
    }
    else {
      $time = DateTime::createFromFormat(DateTime::RFC850, $time,
        new DateTimeZone('GMT'));
    }

    //Get the data
    $this->getData($time, $config, $update);
    // Update time saved in config
    $this->updateConfigPostSync($config);
    parent::submitForm($form, $form_state);
  }

  /**
   * @param \DateTime $time
   * @param bool $update
   * @param \Drupal\Core\Config\Config $config
   */
  private function getData(DateTime &$time, Config &$config, bool $update = FALSE) {
    // Get collection and gallery data
    $config   = $this->config('byu_news_consumer.settings');
    $tagIds  = $config->get('tag_ids');
    $fullUrl = 'https://news.byu.edu/api/Stories?_format=json&categories=all&tags=' . $tagIds;
    $curl    = curl_init($fullUrl);
    curl_setopt_array($curl, $this->options);

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
      exit(1);
    }
    $response = json_decode($response, TRUE);

    // Cycle through all collections
    foreach ($response as $story) {
      $this->saveStory($story, $time, $update, $config);
      unset($story);
    }
    unset($response);
  }

  /**
   * @param array $story
   * @param \DateTime $time
   * @param bool $update
   * @param \Drupal\Core\Config\Config $config
   */
  private function saveStory(
    array &$story, DateTime &$time,
    bool $update, Config &$config
  ) {
    $storyId = $story['StoryId'];
    $storyTitle = $story['Title'];
    $storySummary = $story['Summary'];
    $storyUrl = $story['FullUrl'];
    $storyThumb = $story['FeaturedImgUrl'];
    $storyModified = $story['DatePublished'];

    // Check if modified time is after time
    $storyTime = DateTime::createFromFormat('M-DD-y',
      $storyModified, new DateTimeZone('GMT'));
    if ($update) {
      if ($storyTime < $time) {
        unset($storyTime);
        return;
      }
    }
    unset($storyTime);

    if ($storyThumb !== NULL) {
      $file = File::create([
        'uri' => $storyThumb,
        'alt' => t($storyTitle),
      ]);
      $file->save();
    }

    // Create node from $collection and $keyImageId
    if (!$this->nodeExists('byu_news_story_summary', $storyId)) {
      $node = Node::create([
        'nid'                  => NULL,
        'langcode'             => 'en',
        'uid'                  => $this->uid,
        'type'                 => 'byu_news_story_summary',
        'title_field'          => $storyTitle,
        'title'                => $storyTitle,
        'body'                 => $storySummary,
        'status'               => 1,
        'promote'              => 0,
        'comment'              => 0,
        'field_feature_image' => isset($file) ?
          ['target_id' => $file->id()] : NULL,
        'field_story_id' => $storyId,
        'field_story_link' => $storyUrl
      ]);
       /**THIS CODE ADDS A DATE TO THE NODE*/
               //get date from longer string
               $dateOffset = 38;
               $dateLength = 11;
               $date = substr($storyModified, $dateOffset, $dateLength);
               //get the date; format it as a timestamp
               $format = "M-d-Y";
               $date= DateTime::createFromFormat($format, $date);

               //change time of the node's creation
               $node->setCreatedTime(date_timestamp_get($date));
       /**END ADDED CODE*/
      try {
        $node->save();
      } catch (Exception $e) {
        echo $e->getMessage();
        exit(1);
      }
      if (isset($file)) {
        unset($file);
      }
      unset($node);
      $this->updateConfigPostSync($config);
    }
  }

  /**
   * @param bool $isFullSync
   * @param \Drupal\Core\Config\Config $config
   */
  private function updateConfigPostSync(
    Config &$config,
    bool $isFullSync = FALSE
  ) {
    try {
      $currentTime = new DateTime(NULL, new DateTimeZone('GMT'));
    } catch (Exception $e) {
      echo $e->getMessage();
      exit(1);
    }
    if ($isFullSync) {
      $config->set('last_full_sync', $currentTime->format(
        DateTime::RFC850));
    }
    $config->set('last_sync', $currentTime->format(
      DateTime::RFC850));
    $config->save();
  }

  /**
   * @param null $contentType
   * @param null $id
   *
   * @return int|null|string
   */
  private function nodeExists($contentType = NULL, $id = NULL) {
    $nids = \Drupal::entityQuery('node')
                   ->condition('type', $contentType)
                   ->condition('field_story_id', $id)
                   ->execute();
    $node = reset($nids);

    return !empty($node) ? TRUE : FALSE;
  }
}
