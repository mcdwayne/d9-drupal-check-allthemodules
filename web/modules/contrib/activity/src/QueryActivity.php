<?php

namespace Drupal\activity;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides queries for activity.
 */
class QueryActivity {

  /**
   * The connection to the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a QueryActivity object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   A current user instance.
   */
  public function __construct(Connection $database, AccountProxyInterface $currentUser) {
    $this->database = $database;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Get row for the field needed.
   *
   * @param string $eventId
   *   The action name.
   * @param string $field
   *   The field needed.
   */
  public function getActivityEventField($eventId, $field) {
    $query = $this->database->select('activity_events', 'act')
      ->fields('act', [$field])
      ->condition('event_id', $eventId)->execute()->fetchAll();
    return $query;
  }

  /**
   * Count all from activity table.
   */
  public function countMessages() {
    $query = $this->database->select('activity', 'act');
    $query->fields('act', ['message']);
    return $query;
  }

  /**
   * Get all activities.
   */
  public function getActivities() {
    $query = $this->database->select('activity', 'activity');
    $query->fields('activity', [
      'action_id',
      'event_id',
      'entity_type',
      'nid',
      'uid',
      'created',
      'status',
      'message',
    ]);
    return $query;
  }

  /**
   * Insert into activity.
   *
   * @param string $eventId
   *   The action name.
   * @param string $entityType
   *   The entity type.
   * @param string $nid
   *   The node id.
   * @param string $uid
   *   The user id.
   * @param string $status
   *   The action status.
   * @param string $message
   *   The message that keeps options from configure activity page.
   */
  public function insertActivity($eventId, $entityType, $nid, $uid, $status, $message) {
    $window = $this->validActivity($eventId);
    // Insert only if the current timestamp - activity Window is greater than 0.
    if ($window) {
      $this->database->insert('activity')
        ->fields([
          'event_id' => $eventId,
          'entity_type' => $entityType,
          'nid' => $nid,
          'uid' => $uid,
          'created' => \Drupal::time()->getCurrentTime(),
          'status' => $status,
          'message' => $message,
        ])
        ->execute();
    }
  }

  /**
   * Valid function.
   *
   * @param string $eventId
   *   The action id.
   *
   * @return bool
   *   Return the entity.
   */
  public function validActivity($eventId) {
    $timestamp = \Drupal::time()->getCurrentTime();
    // Get the right value for window option.
    // When to insert logs based on timestamp.
    $queryEvent = $this->database->select('activity_events', 'ev')
      ->fields('ev', ['message'])
      ->condition('event_id', $eventId, '=')
      ->execute()->fetchAll();
    $message = $queryEvent[0]->message;
    $message = json_decode($message);
    $window = $message->window;

    // Count if there are similar actions with the same label
    // in the last $window seconds.
    $count = $this->database->select('activity', 'act')
      ->fields('act', ['action_id'])
      ->condition('event_id', $eventId, '=')
      ->condition('created', $timestamp - $window, '>')
      ->countQuery()
      ->execute()
      ->fetchField();
    return $count == 0;
  }

  /**
   * Get message of specific hook.
   *
   * @param string $hook
   *   Event when the log should be inserted.
   */
  public function getMessage($hook) {
    $query = $this->database->select('activity_events', 'act')
      ->fields('act', ['message', 'event_id'])
      ->condition('hook', $hook)->execute()->fetchAll();
    return $query;
  }

  /**
   * Log actions in table activity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $hook
   *   Event when the log should be inserted.
   */
  public function logActivity(EntityInterface $entity, $hook) {
    $entityNode = NULL;
    $entityUser = NULL;
    $entityComment = NULL;
    $entityType = $entity->getEntityTypeId();
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $activityOption[] = $entity->getType();
        $nodeId = $entity->get('nid')->getValue();
        $nid = $nodeId[0]['value'];
        $userId = $entity->get('uid')->getValue();
        $uid = $userId[0]['target_id'];
        $entityNode = $entity;
        $entityUser = User::load($uid);
        $status = $entity->get('status')->getValue();
        break;

      case 'comment':
        $nodeId = $entity->get('entity_id')->getValue();
        $nid = $nodeId[0]['target_id'];
        $entityNode = Node::load($nid);
        $activityOption[] = $entityNode->getType();
        $entityComment = $entity;
        $userId = $entity->get('uid')->getValue();
        $uid = $userId[0]['target_id'];
        $entityUser = User::load($uid);
        $status = $entity->get('status')->getValue();
        break;

      case 'user':
        $rolesOptions = $entity->get('roles');
        $activityOption = ['0' => 'authenticated'];
        foreach ($rolesOptions as $key => $value) {
          $role = $value->getValue();
          $activityOption[] = $role['target_id'];
        }
        $uid = $this->currentUser->id();
        $nid = NULL;
        $entityUser = $entity;
        $status = $entity->get('status')->getValue();
        if ($status[0]['value'] == FALSE) {
          $status[0]['value'] = 0;
        }
        elseif ($status[0]['value'] == TRUE) {
          $status[0]['value'] = 1;
        }
        break;

      default:
    }
    // Insert into activity table all actions.
    $results = $this->getMessage($hook);
    if (!empty($results)) {
      foreach ($results as $key => $value) {
        $message = json_decode($value->message);
        $types = $message->types;
        if (empty($types)) {
          $roles = $message->roles;
        }
        else {
          $types = $message->types;
        }

        if (!empty($activityOption)) {
          if (!empty($types)) {
            foreach ($activityOption as $act => $activityValue) {
              if (in_array($activityValue, $types)) {
                $activityMessage = \Drupal::token()->replace($message->message, [
                  'node' => $entityNode,
                  'user' => $entityUser,
                  'comment' => $entityComment,
                ]);
                $this->insertActivity($value->event_id, $entityType, $nid, $uid, $status[0]['value'], $activityMessage);
              }
            }
          }
          elseif (!empty($roles)) {
            if (array_intersect($activityOption, $roles)) {
              $activityMessage = \Drupal::token()->replace($message->message, [
                'node' => $entityNode,
                'user' => $entityUser,
                'comment' => $entityComment,
              ]);
              $this->insertActivity($value->event_id, $entityType, $nid, $uid, $status[0]['value'], $activityMessage);
            }
          }
        }
      }
    }
  }

}
