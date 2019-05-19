<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\EntitiesStatisticsInterface;
use Drupal\yasm\Utility\YasmUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * YASM Statistics site users controller.
 */
class Users extends ControllerBase {

  /**
   * The Date Fromatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The datatables service.
   *
   * @var \Drupal\yasm\Services\DatatablesInterface
   */
  protected $datatables;

  /**
   * The entities statitistics service.
   *
   * @var \Drupal\yasm\Services\EntitiesStatisticsInterface
   */
  protected $entitiesStatistics;

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return ($this->moduleHandler->moduleExists('user')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Site users page output.
   */
  public function siteContent(Request $request) {
    $filters = [];

    $year = $request->query->get('year', 'all');
    if (is_numeric($year)) {
      $filters = YasmUtility::getYearFilter('created', $year);
      $this->messenger->addMessage($this->t('"Users by access" and "Lasts users access" tables are not filtered by year.'));
    }

    $first_content_date = $this->entitiesStatistics->getFirstDateContent('user');
    $build['tabs'] = YasmUtility::getYearLinks(date('Y', $first_content_date), $year);
    $build['data'] = $this->buildContent($year, $filters);

    return $build;
  }

  /**
   * Build users page html.
   */
  private function buildContent($year, $filters) {
    // Exclude uid = 0 (anonymous).
    $filter_non_anonymous = [
      [
        'key'      => 'uid',
        'value'    => 0,
        'operator' => '!=',
      ],
    ];
    $filters = array_merge($filters, $filter_non_anonymous);

    $usercount = $this->entitiesStatistics->count('user', $filters);
    if ($usercount > 0) {
      // Build user by status table.
      $users_status = $this->entitiesStatistics->aggregate('user', ['uid' => 'COUNT'], 'status', array_merge($filters, $filter_non_anonymous));

      $status_label = [
        1 => $this->t('Active'),
        0 => $this->t('Blocked'),
      ];
      $rows = [];
      if (!empty($users_status)) {
        foreach ($users_status as $users) {
          $rows[] = [
            $status_label[$users['status']],
            $users['uid_count'],
            round($users['uid_count'] * 100 / $usercount, 2) . '%',
          ];
        }
      }

      $table_by_status = YasmUtility::table([
        $this->t('Status'),
        $this->t('Count'),
        $this->t('Percentage'),
      ], $rows, 'users_status');

      // Build user by role table, exclude uid = 0 (anonymous).
      $users_roles = $this->entitiesStatistics->aggregate('user', ['uid' => 'COUNT'], 'roles', array_merge($filters, $filter_non_anonymous));
      $rows = [];
      if (!empty($users_roles)) {
        foreach ($users_roles as $users) {
          if (!empty($users['roles_target_id'])) {
            $role = $this->entityTypeManager->getStorage('user_role')->load($users['roles_target_id'])->label();
          }
          else {
            $role = $this->t('None');
          }

          $rows[] = [
            $role,
            $users['uid_count'],
            round($users['uid_count'] * 100 / $usercount, 2) . '%',
          ];
        }
      }
      $table_by_role = YasmUtility::table([
        $this->t('Role'),
        $this->t('Count'),
        $this->t('Percentage'),
      ], $rows, 'users_role');

      // Count users that has never access.
      $rows = [];
      $never = $this->entitiesStatistics->count('user', ['access' => 0]);
      $rows[] = [
        $this->t('Never'),
        $never,
        round($never * 100 / $usercount, 2) . '%',
      ];
      // Build user by last access table.
      $access = [
        strtotime('-1 day')   => $this->t('Today'),
        strtotime('-1 week')  => $this->t('Last week'),
        strtotime('-1 month') => $this->t('Last month'),
        strtotime('-1 year')  => $this->t('Last year'),
      ];
      foreach ($access as $key => $value) {
        $count = $this->entitiesStatistics->count('user', [
          [
            'key'      => 'access',
            'value'    => $key,
            'operator' => '>',
          ],
        ]);
        $rows[] = [
          $value,
          $count,
          round($count * 100 / $usercount, 2) . '%',
        ];
      }

      $table_by_access = YasmUtility::table([
        $this->t('Access'),
        $this->t('Count'),
        $this->t('Percentage'),
      ], $rows, 'users_access');

      // Build lasts users access table.
      $query = $this->entityTypeManager->getStorage('user')->getQuery();
      $query->condition('uid', 0, '!=')
        ->sort('access', 'DESC')
        ->range(0, 10);
      $uids = $query->execute();
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
      $rows = [];
      foreach ($users as $user) {
        $rows[] = [
          'name'   => $user->getDisplayName(),
          'access' => $this->dateFormatter->formatTimeDiffSince($user->getLastAccessedTime()),
        ];
      }
      $table_lasts_access = YasmUtility::table([
        $this->t('Access'),
        $this->t('User'),
      ], $rows);

      // Build new users monthly table.
      $dates = YasmUtility::getLastMonths($year);
      // Collect data for all cols.
      $rows = $labels = [];
      foreach ($dates as $date) {
        // Filter data.
        $labels[] = $date['label'];
        $filter = YasmUtility::getIntervalFilter('created', $date['max'], $date['min']);
        $rows['data'][] = $this->entitiesStatistics->count('user', $filter);
      }
      $table_new_users_monthly = YasmUtility::table($labels, $rows, 'users_monthly');

      // Build content output.
      $build = [];

      $build[] = YasmUtility::markup($this->t('There are currently @count users.', ['@count' => $usercount]));

      $cards[] = [
        YasmUtility::title($this->t('Users created by status'), 'far fa-user'),
        $table_by_status,
      ];
      $cards[] = [
        YasmUtility::title($this->t('Users created by role'), 'far fa-user'),
        $table_by_role,
      ];
      $cards[] = [
        YasmUtility::title($this->t('Users by access'), 'far fa-user'),
        $table_by_access,
      ];
      $cards[] = [
        YasmUtility::title($this->t('Lasts users access'), 'far fa-user'),
        $table_lasts_access,
      ];
      // First region in two cols.
      $build[] = YasmUtility::columns($cards, ['yasm-users'], 2);

      $cards = [];
      $cards[] = [
        YasmUtility::title($this->t('New users monthly'), 'far fa-user'),
        $table_new_users_monthly,
      ];
      // Second region in one col.
      $build[] = YasmUtility::columns($cards, ['yasm-users'], 1);

      $build[] = [
        '#attached' => [
          'library' => ['yasm/global', 'yasm/fontawesome', 'yasm/datatables'],
          'drupalSettings' => ['datatables' => ['locale' => $this->datatables->getLocale()]],
        ],
        '#cache' => [
          'tags' => ['user_list'],
          'max-age' => 3600,
        ],
      ];

      return $build;
    }

    return ['#markup' => $this->t('No data found.')];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, DatatablesInterface $datatables, EntitiesStatisticsInterface $entities_statistics) {
    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $date_formatter;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->datatables = $datatables;
    $this->entitiesStatistics = $entities_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.datatables'),
      $container->get('yasm.entities_statistics')
    );
  }

}
