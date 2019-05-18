<?php

namespace Drupal\ptalk\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\CurrentRouteMatch;


/**
 * Defines cache context for the thread page.
 *
 * Cache context ID: 'ptalk_thread_participant_id'.
 */
class ThreadCacheContext implements CacheContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ThreadCacheContext class.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match, AccountInterface $current_user) {
    $this->routeMatch = $current_route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Private Conversation');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($thread = $this->routeMatch->getParameter('ptalk_thread')) {
      if ($thread->participantOf($this->currentUser)) {
        return $this->currentUser->id();
      }
      else {
        return $thread->getOwnerId();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
