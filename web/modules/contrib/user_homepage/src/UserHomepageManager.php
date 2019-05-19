<?php

namespace Drupal\user_homepage;

use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides common functionality to manage users homepages.
 */
class UserHomepageManager implements UserHomepageManagerInterface {

  /**
   * The database connection used to get / set users homepages.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The request stack used to assemble paths for a user homepage.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  private $currentPathStack;

  /**
   * UserHomepageManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection used to get / set users homepages.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack used to assemble paths for a user homepage.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack.
   */
  public function __construct(Connection $connection, RequestStack $requestStack, CurrentPathStack $currentPathStack) {
    $this->connection = $connection;
    $this->requestStack = $requestStack;
    $this->currentPathStack = $currentPathStack;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserHomepage($uid) {
    $args = [':uid' => $uid];
    $query = $this->connection->query("SELECT path FROM {user_homepage} WHERE uid = :uid", $args);
    $homepage_path = $query->fetchField() ?: NULL;
    return $homepage_path;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserHomepage($uid, $path) {
    try {
      // A merge operation tackles the Insert or Update action, as appropriate.
      $merge_query = $this->connection->merge('user_homepage');
      $merge_result = $merge_query
        ->key(['uid' => $uid])
        ->fields([
          'uid' => $uid,
          'path' => $path,
        ])
        ->execute();
    }
    catch (Exception $e) {
      return FALSE;
    }
    return (in_array($merge_result, [$merge_query::STATUS_INSERT, $merge_query::STATUS_UPDATE]));
  }

  /**
   * {@inheritdoc}
   */
  public function unsetUserHomepage($uid) {
    try {
      $delete_query = $this->connection->delete('user_homepage');
      $result = $delete_query->condition('uid', $uid)
        ->execute();
    }
    catch (Exception $e) {
      return FALSE;
    }
    return ($result >= 1);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHomepagePathFromCurrentRequest() {
    // Set options for the internal url of the current path. 'alias' is set to
    // TRUE to prevent the Url class from transforming the path into its alias.
    $options = [
      'query' => $this->requestStack->getCurrentRequest()->query->all(),
      'alias' => TRUE,
    ];

    $homepage_url = Url::fromUri('internal:' . $this->currentPathStack->getPath(), $options)->toString();
    return $homepage_url;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveUserRedirection(AccountInterface $account) {
    if ($homepage = $this->getUserHomepage($account->id())) {
      $parsed_homepage = parse_url($homepage);
      $options = [];
      if (isset($parsed_homepage['query'])) {
        parse_str($parsed_homepage['query'], $options['query']);
      }

      // Set 'destination' param for Drupal core to process.
      if ($current_request = $this->requestStack->getCurrentRequest()) {
        $destination_path = Url::fromUri('internal:' . $parsed_homepage['path'], $options)->toString();
        $current_request->query->set('destination', $destination_path);
        return TRUE;
      }
    }
    return FALSE;
  }

}
