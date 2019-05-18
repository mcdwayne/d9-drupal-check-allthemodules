<?php

namespace Drupal\mattermost_integration\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\mattermost_integration\Services\MattermostApi;
use Drupal\mattermost_integration\Services\MattermostDrupalMapper;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EndpointController handles Outgoing Webhooks from Mattermost.
 *
 * @package Drupal\mattermost_integration\Controller
 */
class EndpointController extends ControllerBase {

  const TYPE_SUCCESS = 'Success';
  const TYPE_ERROR = 'Error';
  const TYPE_NOTICE = 'Notice';

  /**
   * The entity manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Mattermost API service.
   *
   * @var MattermostApi
   */
  protected $mattermostApi;

  /**
   * The webhook entity stored globally for this class.
   *
   * @var \Drupal\mattermost_integration\Entity\OutgoingWebhook
   */
  protected $webhookEntity;

  /**
   * The Mattermost Drupal Mapper service.
   *
   * @var \Drupal\mattermost_integration\Services\MattermostDrupalMapper
   */
  protected $mattermostDrupalMapper;

  /**
   * EndpointController constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\mattermost_integration\Services\MattermostApi $mattermost_api
   *   The Mattermost API service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MattermostApi $mattermost_api, MattermostDrupalMapper $mattermost_drupal_mapper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mattermostApi = $mattermost_api;
    $this->mattermostDrupalMapper = $mattermost_drupal_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('mattermost_integration.api'),
      $container->get('mattermost_integration.mattermost_drupal_mapper')
    );
  }

  /**
   * Method to handle the incoming requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The value of the incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON string containing the result that will be returned to
   *   Mattermost.
   *
   * @TODO: Create MattermostRequest class?
   * @TODO: Create MattermostResponse class?
   */
  public function requestHandler(Request $request) {
    // Decode the request body.
    $request = Json::decode($request->getContent());

    $entities = $this->entityTypeManager->getStorage('outgoing_webhook')->loadMultiple();

    // Store channel IDs in a separate array.
    $channel_ids = [];
    foreach ($entities as $entity_key => $entity_value) {
      /* @var $entity_value \Drupal\mattermost_integration\Entity\OutgoingWebhook*/
      $channel_ids[$entity_key] = $entity_value->getChannelId();
    }

    // Search the channel_id from the request in the stored entities.
    $search_array = array_search($request['channel_id'], $channel_ids);

    // If $search_array finds a match it will return the array key of the match.
    // If it is false it will return FALSE. The matching channel_id may be 0
    // which will also be FALSE therefore we must check for same type too.
    if ($search_array === FALSE) {
      return $this->responseBuilder($this::TYPE_ERROR, 'No matching channel_id was found.');
    }

    // Check if the tokens match.
    /* @var $webhook_entity \Drupal\mattermost_integration\Entity\OutgoingWebhook*/
    $webhook_entity = $entities[$search_array];
    if ($webhook_entity->getWebhookToken() != $request['token']) {
      return $this->responseBuilder($this::TYPE_ERROR, 'The webhook tokens did not match.');
    }
    $this->webhookEntity = $webhook_entity;

    // Retrieve the full post from the Mattermost server.
    $mattermost_post = $this->mattermostApi->mattermostApiGetPost($request['post_id'], $request['team_id'], $request['channel_id']);

    // Check if the $mattermost_post contains an error or if it is empty.
    if (isset($mattermost_post['status_code'])) {
      return $this->responseBuilder($this::TYPE_ERROR, $mattermost_post['status_code'] . ': ' . $mattermost_post['message']);
    }
    elseif (empty($mattermost_post)) {
      return $this->responseBuilder($this::TYPE_ERROR, 'Received an empty object looking up the post. Is your Mattermost URL configured correctly?');
    }

    $request_post_id = $mattermost_post['order'][0];
    $request_post_value = $mattermost_post['posts'][$request_post_id];

    // Check if post contains file(s).
    $file_names = NULL;
    if (isset($request_post_value['file_ids'])) {
      foreach ($request_post_value['file_ids'] as $file_id) {
        $file_names[] = $this->mattermostApi->mattermostApiGetFile($file_id);
      }
    }

    // Check if the request is a reply or a message by checking if the
    // root_id of the current post is empty. We get the current request ID
    // from the order array. This is an array holding the post ID of the
    // current request. We look up this post ID in the posts array and
    // check if that array has a root_id.
    // @TODO: Return a notice if files were found but couldn't be attached.
    if (empty($request_post_value['root_id'])) {
      $node = $this->createNode($request_post_value);

      // Check if node is created successfully.
      if ($node === FALSE) {
        return $this->responseBuilder($this::TYPE_ERROR, 'Could not create node.');
      }
      $url = Url::fromRoute('entity.node.canonical', ['node' => $node], ['absolute' => TRUE])->toString();

      // Attach files if any.
      if ($file_names !== NULL) {
        foreach ($file_names as $file_name) {
          $this->attachFile($node, $file_name);
        }
      }

      return $this->responseBuilder($this::TYPE_SUCCESS, $url);
    }
    else {
      $comment = $this->createReply($request_post_value);

      // Check if comment is created successfully.
      if ($comment === FALSE) {
        return $this->responseBuilder($this::TYPE_ERROR, 'Could not create comment.');
      }

      $url = Url::fromRoute('entity.comment.canonical', ['comment' => $comment], ['absolute' => TRUE])->toString();

      // Attach files if any.
      if ($file_names !== NULL) {
        foreach ($file_names as $file_name) {
          $this->attachFile($comment, $file_name, FALSE);
        }
      }

      return $this->responseBuilder($this::TYPE_SUCCESS, $url);
    }

  }

  /**
   * Method for building a JSON response.
   *
   * @param string $type
   *   The type of the message. Must be one of the TYPE_ constants.
   * @param string $message
   *   The message to respond.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Generated JsonResponse from input.
   */
  public function responseBuilder($type, $message) {
    return new JsonResponse(['text' => "$type: $message"]);
  }

  /**
   * Handler function for creating a node.
   *
   * @param array $post
   *   The full POST object containing the post values.
   *
   * @return int|bool
   *   The create Node ID on success or FALSE on failure.
   *
   * @TODO: Don't rely on 'body' field.
   * @TODO: Don't rely on 'full_html' format.
   */
  public function createNode(array $post) {
    $message = $post['message'];
    if ($this->webhookEntity->getConvertMarkdown()) {
      $parsedown = new \Parsedown();
      $message = $parsedown->text($post['message']);
    }

    $node = Node::create(['type' => $this->webhookEntity->getContentType()]);
    $node->setTitle($this->truncateToWords($post['message'], 4, TRUE, TRUE));
    $node->setCreatedTime(substr($post['create_at'], 0, 10));
    $node->setOwnerId($this->mattermostDrupalMapper->getUserId($post['user_id']));
    $node->set('body', [
      'value' => $message,
      'format' => 'full_html',
    ]);
    $node->set('mattermost_integration_post_id', [
      'value' => $post['id'],
    ]);
    $save_node = $node->save();
    $nid = $node->id();

    return $save_node ? $nid : FALSE;
  }

  /**
   * Handler function for creating a reply on a node.
   *
   * @param array $post
   *   The full POST object containing the post values.
   *
   * @return int|bool
   *   Comment ID on success or FALSE on failure.
   *
   * @TODO: Don't rely on 'comment_body' field.
   * @TODO: Don't rely on 'full_html' format.
   */
  public function createReply(array $post) {
    $message = $post['message'];
    if ($this->webhookEntity->getConvertMarkdown()) {
      $parsedown = new \Parsedown();
      $message = $parsedown->text($post['message']);
    }

    $values = [
      'entity_type' => 'node',
      'entity_id' => $this->mattermostDrupalMapper->getParent($post['root_id']),
      'comment_type' => $this->webhookEntity->getCommentType(),
      'field_name' => $this->webhookEntity->getCommentField(),
    ];

    $comment = Comment::create($values);
    $comment->setSubject($this->truncateToWords($post['message'], 2, TRUE, TRUE));
    $comment->setCreatedTime(substr($post['create_at'], 0, 10));
    $comment->setOwnerId($this->mattermostDrupalMapper->getUserId($post['user_id']));
    $comment->set('comment_body', [
      'value' => $message,
      'format' => 'full_html',
    ]);
    $save_comment = $comment->save();
    $cid = $comment->id();

    return $save_comment ? $cid : FALSE;
  }

  /**
   * Method for attaching a file to a node or comment.
   *
   * @param int $target
   *   The ID of the target node or comment.
   * @param string $file_name
   *   The name of the file to attach.
   * @param bool $is_node
   *   If the $target id is a node ID or a comment ID.
   */
  public function attachFile($target, $file_name, $is_node = TRUE) {
    $file_data = file_get_contents(file_directory_temp() . '/' . $file_name);
    $file = file_save_data($file_data, 'public://' . $file_name);

    if ($is_node) {
      /* @var $node \Drupal\node\Entity\Node */
      $node = $this->entityTypeManager->getStorage('node')->load($target);
      if ($node->hasField('field_mattermost_file')) {
        $files = $node->get('field_mattermost_file')->getValue();
        $files[] = $file->id();
        $node->set('field_mattermost_file', $files);
      }

      $node->save();
    }
    else {
      $comment = Comment::load($target);
      if ($comment->hasField('field_mattermost_file')) {
        $files = $comment->get('field_mattermost_file')->getValue();
        $files[] = $file->id();
        $comment->set('field_mattermost_file', $files);
      }
      $comment->save();
    }
  }

  /**
   * Method for truncating a string to a number of words.
   *
   * @param string $string
   *   The string to truncate.
   * @param int $word_amount
   *   The amount of words the string must be truncated to.
   * @param bool $add_ellipsis
   *   Whether or not add an ellipsis.
   * @param bool $filter_characters
   *   Whether or not to filter out characters that are not A-Z, a-z, 0-9 or
   *    hyphens.
   *
   * @return string
   *   The truncated string.
   */
  public function truncateToWords($string, $word_amount, $add_ellipsis = FALSE, $filter_characters = FALSE) {
    if ($filter_characters) {
      // Replace spaces with a placeholder so we can put the spaces back later.
      $string = str_replace(' ', '<space>', $string);

      // Strip everything but A-Z, a-z and 0-9.
      $string = preg_replace('/[^A-Za-z0-9\<space>\-]/', '', $string);

      // Restore the spaces.
      $string = str_replace('<space>', ' ', $string);
    }

    if (str_word_count($string) > $word_amount) {
      $words = explode(' ', $string);
      $string = array_slice($words, 0, $word_amount);
      $string = implode(' ', $string);

      if ($add_ellipsis) {
        $string = $string . '...';
      }
    }

    return $string;
  }

}
