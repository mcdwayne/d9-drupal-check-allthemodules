<?php

namespace Drupal\role_paywall_article_test;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\User\Entity\User;
use Drupal\Node\Entity\Node;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Manages access to test articles.
 */
class ArticleTestManager {

  const DAY_IN_SECONDS = 24 * 60 * 60;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;


  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $configuration;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datatime\TimeInterface
   */
  private $timeService;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection, TimeInterface $time_service) {
    $this->connection = $connection;
    // @todo inject this!
    $this->configuration = \Drupal::config('role_paywall_article_test.settings');
    $this->timeService = $time_service;
  }

  /**
   * Returns the flag object used to control the access to 1-article-test.
   *
   * @return FlagInterface|null
   *   The flag object, or NULL if not the flag could not be found.
   */
  public function getFlag() {
    if ($flag_id = \Drupal::config('role_paywall_article_test.settings')->get('access_flag')) {
      $flag_service = \Drupal::service('flag');
      $flag = $flag_service->getFlagById($flag_id);
      return $flag;
    }
  }

  /**
   * Returns the timestamp of the last 1-article-test of a given user.
   *
   * @return int
   *   The timestamp of the last article test of the given user, or
   *   0 if there is none.
   *
   * @see FlagCount::getUserFlagFlaggingCount()
   * @todo Cache the count.
   */
  public function getLastTestTimestamp(AccountInterface $user) {
    $flag = $this->getFlag();
    $flag_id = $flag->id();
    $uid = $user->id();

    // Only one flag is allowed for each entity-user pair.
    $query = $this->connection->select('flagging', 'f')
      ->fields('f', ['created'])
      ->condition('flag_id', $flag_id)
      ->condition('uid', $uid)
      ->range(0, 1);

    $last_test_timestamp = $query->execute()->fetchField();
    // No record returns 0.
    return (int) $last_test_timestamp;
  }

  /**
   * Returns whether a given user can test an article.
   *
   * Works for users with and without previous usages of the 1-article-test.
   *
   * @param AccountInterface $user
   *   The user to check.
   *
   * @return bool
   *   TRUE if the given user can test an/another article.
   */
  public function hasUserAccessToNextTest(AccountInterface $user) {
    $time_interval = $this->configuration->get('blocking_period_days') * self::DAY_IN_SECONDS;
    $next_test_available = $this->getLastTestTimestamp($user) + $time_interval;
    return $next_test_available < $this->timeService->getRequestTime();
  }

  /**
   * Grants article test access.
   *
   * @param UserInterface $user
   *   The user to grant access to.
   * @param Node $node
   *   The node to grant access to.
   */
  public function grantArticleTestAccess(User $user, Node $node) {
    $configuration = \Drupal::config('role_paywall_article_test.settings');
    $flag_id = $configuration->get('access_flag');
    if (empty($flag_id)) {
      // @todo inject the service.
      \Drupal::logger('role_paywall_article_test')->error('You should to configure the article test flag in /admin/config/content/role_paywall/article_test');
      return;
    }
    // @todo inject the service.
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById($flag_id);

    $flagged = $flag_service->getFlagging($flag, $node, $user);
    if (is_null($flagged)) {
      $flag_service->flag($flag, $node, $user, NULL, $this->timeService->getRequestTime());
    }
  }

  /**
   * Renders the barrier.
   *
   * @return array
   *   The render array for the barrier.
   */
  public function renderBarrier() {
    $form = \Drupal::formBuilder()->getForm('Drupal\role_paywall_article_test\Form\ArticleTestForm');

    // Build action links.
    $items = [];
    $items['login'] = [
      '#type' => 'link',
      '#title' => t('Log in'),
      '#url' => Url::fromRoute('user.login', [], [
        'query' => [
          'destination' => Url::fromRoute('<current>')->toString(),
        ],
        'attributes' => [
          'title' => t('Log in with your existing account.'),
          'class' => ['login-link'],
        ],
      ]),
    ];
    $items['request_password'] = [
      '#type' => 'link',
      '#title' => t('Reset your password'),
      '#url' => Url::fromRoute('user.pass', [], [
        'attributes' => [
          'title' => t('Send password reset instructions via email.'),
          'class' => ['request-password-link'],
        ],
      ]),
    ];

    // @todo make the strings configruable
    return [
      '#title' => t('Continue reading?'),
      'description' => [
        '#type' => 'markup',
        '#markup' => t('If you are already a subscriber, please login. Enter your e-mail address to read the article for free!'),
      ],
      'user_register_form' => $form,
      'user_links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

}
