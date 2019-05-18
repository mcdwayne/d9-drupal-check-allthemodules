<?php

namespace Drupal\campaignmonitor_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class CampaignMonitorUserController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage,
  UserDataInterface $user_data, LoggerInterface $logger) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * View subscriptions.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'user.page' route with the '_user_is_logged_in'
   * requirement.
   */
  public function subscriptionPage() {

    // Get the user's current subscriptions.
    $current_user = \Drupal::currentUser();
    $config = \Drupal::config('campaignmonitor_user.settings');

    $email = $current_user->getEmail();
    $subscriptions = campaignmonitor_user_get_user_subscriptions($email, 'names');

    $subscriptions_empty_message = '';

    $content['subscription_heading'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'subscription-heading',
        ],
      ],
    ];
    $content['subscription_heading']['heading'] = [
      '#markup' => $config->get('subscription_heading'),
    ];

    $content['subscription_text'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'subscription-text',
        ],
      ],
    ];
    $content['subscription_text']['text'] = [
      '#markup' => $config->get('subscription_text'),
    ];

    $content['subscriptions_table'] = [
      '#type' => 'table',
      '#header' => [t('Name')],
      '#empty' => $subscriptions_empty_message,
    ];

    foreach ($subscriptions as $list_id => $name) {
      $content['subscriptions_table'][$list_id]['name'] = [
        '#markup' => $name,
      ];
    }
    return [
      '#theme' => 'campaignmonitor_user_profile',
      '#content' => $content,
      '#cache' => [
        'contexts' => [
          'user',
        ],
      ],
    ];
  }

}
