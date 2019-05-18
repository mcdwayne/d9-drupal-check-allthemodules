<?php

namespace Drupal\mattermost_integration\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for retrieving Drupal data mapped from Mattermost data.
 *
 * @package Drupal\mattermost_integration\Services
 */
class MattermostDrupalMapper {
  protected $entityTypeManager;
  protected $logger;

  /**
   * MattermostDrupalMapper constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   An instance of EntityTypeManagerInterface.
   * @param LoggerChannelFactoryInterface $logger_channel_factory
   *   An instance of LoggerChannelFactoryInterface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_channel_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_channel_factory->get('mattermost_integration');
  }

  /**
   * Method for looking up a user ID from the Mattermost request.
   *
   * @param int $request_user_id
   *   The user_id found in the incoming request.
   *
   * @return int
   *   The corresponding UID, if no match found returns 0 (Anonymous).
   */
  public function getUserId($request_user_id) {
    try {
      $user_ids = $this->entityTypeManager->getStorage('user')->getQuery()->execute();
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($user_ids);
    } catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Failed to load users because of an invalid plugin definition.');
      return 0;
    }

    $mattermost_user_ids = [];
    foreach ($users as $user_id => $user) {
      /* @var $user \Drupal\user\Entity\User */
      $mattermost_user_id = $user->get('field_mattermost_user_id')->getValue();
      if (!empty($mattermost_user_id)) {
        $mattermost_user_ids[$user_id] = $mattermost_user_id[0]['value'];
      }
    }

    // $search_array holds the User ID under which the incoming request user_id
    // is found.
    $search_array = array_search($request_user_id, $mattermost_user_ids);

    // If the user_id is not found on the Drupal site, return UID 0.
    return $search_array === FALSE ? 0 : $search_array;
  }

  /**
   * Method for getting a NID of a root_id.
   *
   * @param string $root_id
   *   The post ID which to look up.
   *
   * @return bool|mixed
   *   The NID of the matching parent node, false on no match.
   */
  public function getParent($root_id) {
    try {
      $node_ids = $this->entityTypeManager->getStorage('node')->getQuery()->execute();
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($node_ids);
    } catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Failed to load nodes because of an invalid plugin definition.');
      return 0;
    }

    $root_ids = [];
    foreach ($nodes as $node_id => $node) {
      /* @var $node \Drupal\node\Entity\Node */
      if ($node->hasField('mattermost_integration_post_id')) {
        $root_id = $node->get('mattermost_integration_post_id')->getValue();
        if (!empty($root_id)) {
          $root_ids[$node_id] = $root_id;
        }
      }
    }

    $search_array = array_search($root_id, $root_ids);

    return $search_array;
  }

}
