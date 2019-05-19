<?php

namespace Drupal\twitter_entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\twitter_entity\TwitterEntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TwitterPageController.
 *
 * @package Drupal\twitter_entity\Controller
 */
class TwitterPageController extends ControllerBase {

  /**
   * TwitterPageController constructor.
   *
   * @param \Drupal\twitter_entity\TwitterEntityManager $twitterManager
   *   Twitter manager service.
   */
  public function __construct(TwitterEntityManager $twitterManager) {
    $this->twitterManager = $twitterManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $twitterManager = $container->get('twitter_entity.twitter_manager');

    return new static($twitterManager);
  }

  /**
   * Manual Tweets pull.
   *
   * @return array
   *   Page with information about pulled Tweets.
   */
  public function manualPull() {
    $pullStatus = $this->twitterManager->pull();

    if (is_array($pullStatus) && !empty($pullStatus['error'])) {
      return [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $pullStatus['error'],
      ];
    }

    return [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $pullStatus,
    ];
  }

}
