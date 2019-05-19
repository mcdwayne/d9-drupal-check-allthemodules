<?php

namespace Drupal\social_share;

use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\typed_data\PlaceholderResolverTrait;

/**
 * Provides the social share link manager.
 */
trait SocialShareLinkManagerTrait {

  /**
   * The social share link manager used.
   *
   * @var \Drupal\social_share\SocialShareLinkManagerInterface
   */
  protected $socialShareLinkManager;

  /**
   * Sets the social share link manager.
   *
   * @param \Drupal\social_share\SocialShareLinkManagerInterface $socialShareLinkManager
   *   The social share link manager.
   *
   * @return $this
   */
  public function setSocialShareLinkManager(SocialShareLinkManagerInterface $socialShareLinkManager) {
    $this->socialShareLinkManager = $socialShareLinkManager;
    return $this;
  }

  /**
   * Gets the social share link manager.
   *
   * @return \Drupal\social_share\SocialShareLinkManagerInterface
   *   The social share link manager.
   */
  public function getSocialShareLinkManager() {
    if (empty($this->socialShareLinkManager)) {
      $this->socialShareLinkManager = \Drupal::service('social_share.link_manager');
    }

    return $this->socialShareLinkManager;
  }

}
