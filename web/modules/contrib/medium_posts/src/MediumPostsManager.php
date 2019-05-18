<?php

namespace Drupal\medium_posts;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserDataInterface;
use JonathanTorres\MediumSdk\Medium;
use Symfony\Component\Config\Definition\Exception\Exception;
use Drupal\medium_posts\Event\MediumPublishEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Medium Posts Manager.
 *
 * @package Drupal\medium_posts
 */
class MediumPostsManager implements MediumPostsManagerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * User data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Medium settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mediumSettings;

  /**
   * Medium node type.
   *
   * @var array|mixed|null
   */
  protected $nodeType;

  /**
   * MediumPostsManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserDataInterface $user_data, LoggerChannelFactoryInterface $logger_factory, Connection $database, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->userData = $user_data;
    $this->loggerFactory = $logger_factory;
    $this->database = $database;
    $this->eventDispatcher = $event_dispatcher;
    $this->mediumSettings = $this->configFactory->get('medium_posts.settings');
    $this->nodeType = $this->mediumSettings->get('node_type');
  }

  /**
   * {@inheritdoc}
   */
  public function isMediumNodeType(NodeInterface $node) {
    if ($node->getType() === $this->nodeType) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished($uuid) {
    $result = $this->database->select('medium_posts', 'mp')
      ->fields('mp', ['mid'])
      ->condition('uuid', $uuid)
      ->execute()
      ->fetchAll();

    if ($result) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function publish(NodeInterface $node) {
    $author_uid = $node->getOwnerId();
    $token = $this->getToken($author_uid);

    // No medium token for this author user, do nothing.
    if ($token === FALSE) {
      return;
    }

    $medium = new Medium($token);
    $user = $medium->getAuthenticatedUser();

    // Render body html.
    $content_view = $node->body->view('full');
    $content = render($content_view);
    $content_html = $content->__toString();

    // Get tags.
    $tag_names = [];
    $tags = $node->get('field_tags');
    foreach ($tags as $tag) {
      $tag_names[] = $tag->entity->name->value;
    }

    $data = [
      'title' => $node->getTitle(),
      'contentFormat' => 'html',
      'content' => $content_html,
      'tags' => $tag_names,
      'publishStatus' => $this->mediumSettings->get('publish_status'),
    ];

    try {
      if (isset($user->errors)) {
        $error = reset($user->errors);
        throw new Exception($error->message);
      }

      $post = $medium->createPost($user->data->id, $data);

      if (isset($post->data)) {
        $this->log($node->uuid(), $post->data);

        // Dispatching MediumPublishEvent event.
        $event = new MediumPublishEvent($node, $post->data->url);
        $this->eventDispatcher->dispatch(MediumPublishEvent::POST_PUSHED, $event);
      }
      elseif (isset($post->errors)) {
        $error = reset($post->errors);
        throw new Exception($error->message);
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('medium_posts')->info($e->getMessage());
      drupal_set_message($e->getMessage(), 'error');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getMediumPostUrl($uuid) {
    $url = $this->database->select('medium_posts', 'mp')
      ->fields('mp', ['url'])
      ->condition('uuid', $uuid)
      ->execute()
      ->fetchField();

    return $url;
  }

  /**
   * Record the push response in Drupal.
   *
   * @param string $uuid
   *   Node uuid.
   * @param mixed $post_data
   *   Medium response data.
   */
  protected function log($uuid, $post_data) {
    $fields = [
      'url' => $post_data->url,
      'medium_post_id' => $post_data->id,
      'uuid' => $uuid,
    ];

    $mid = $this->database->insert('medium_posts')
      ->fields($fields)
      ->execute();

    if ($mid) {
      $message = t('Post <strong>@title</strong> has been pushed to medium.com at <a href="@link">@link</a>', ['@title' => $post_data->title, '@link' => $post_data->url]);
      $this->loggerFactory->get('medium_posts')->info($message);
      drupal_set_message($message, 'status');
    }
    else {
      throw new Exception('A error happens in logging medium record.');
    }
  }

  /**
   * Get user integration token for medium API connection.
   *
   * @param int $uid
   *   User uid.
   *
   * @return string|bool
   *   A string of medium user integration token or FALSE if no token can be
   *   found.
   */
  protected function getToken($uid) {
    $result = $this->userData->get('medium_posts', $uid, 'token');

    if (is_string($result) && !empty($result)) {
      return $result;
    }

    return FALSE;
  }

}
