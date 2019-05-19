<?php

namespace Drupal\yasm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\GroupMembership;
use Drupal\group\Entity\Group;
use Drupal\yasm\Services\DatatablesInterface;
use Drupal\yasm\Services\GroupsStatisticsInterface;
use Drupal\yasm\Utility\YasmUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YASM Statistics site groups controller.
 */
class Groups extends ControllerBase {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

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
   * @var \Drupal\yasm\Services\GroupsStatisticsInterface
   */
  protected $groupsStatistics;

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
    return ($this->moduleHandler->moduleExists('group')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Site groups page output.
   */
  public function siteContent() {
    // Get all groups.
    $site_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();

    // Build array content output.
    $build = [];
    $build[] = YasmUtility::title($this->t('Groups contents'), 'fas fa-users');
    $build[] = $this->buildGroupsContentsTable($this->getGroupsContents($site_groups));

    $build[] = YasmUtility::title($this->t('Groups members'), 'fas fa-users');
    $build[] = $this->buildGroupsMembersTable($this->getGroupsMembers($site_groups));

    return $this->buildContents($build);
  }

  /**
   * My groups page output.
   */
  public function myContent() {
    $user = $this->currentUser;
    $this->messenger->addMessage($this->t('Statistics filtered with your groups membership: @name.', [
      '@name' => $user->getDisplayName(),
    ]));

    if ($user_groups = $this->groupMembershipLoader->loadByUser($user)) {

      $groupsContents = $this->getGroupsContents($user_groups);
      $groupsMembers = $this->getGroupsMembers($user_groups);

      // Build content output.
      $cards = [];
      foreach ($groupsContents as $id => $group) {
        $card = [];
        $card[] = YasmUtility::title($groupsContents[$id]['name'] . ' (' . $groupsContents[$id]['type'] . ')', 'fas fa-users', ['panel-title']);
        $card[] = YasmUtility::markup($this->t('Members') . ': ' . $groupsMembers[$id]['members'], 'fas fa-user', ['yasm-group-datakey fa-lg']);
        $card[] = YasmUtility::markup($this->t('Nodes') . ': ' . $groupsContents[$id]['nodes'], 'far fa-file-alt', ['yasm-group-datakey fa-lg']);

        // Contents table by type.
        if (!empty($groupsContents[$id]['by_type'])) {
          $rows = [];
          $rows[] = [
            'data' => [$this->t('Total contents'), $groupsContents[$id]['nodes']],
            'class' => ['total-row'],
          ];
          foreach ($groupsContents[$id]['by_type'] as $value) {
            $rows[] = [$value['type'], $value['count']];
          }
          $card[] = YasmUtility::title($this->t('Contents by type'), 'far fa-file-alt');
          $card[] = YasmUtility::table([$this->t('Type'), $this->t('Count')], $rows, 'my_groups_contents');
        }

        // Members table by role.
        if (!empty($groupsMembers[$id]['by_role'])) {
          $rows = [];
          $rows[] = [
            'data' => [$this->t('Members'), $groupsMembers[$id]['members']],
            'class' => ['total-row'],
          ];
          foreach ($groupsMembers[$id]['by_role'] as $value) {
            $rows[] = [$value['role'], $value['count']];
          }
          $card[] = YasmUtility::title($this->t('Members by group role'), 'fas fa-user');
          $card[] = YasmUtility::table([$this->t('Role'), $this->t('Count')], $rows, 'my_groups_roles');
        }

        $cards[] = $card;
      }

      $build = YasmUtility::columns($cards, ['yasm-groups'], 2);

      $build = $this->buildContents($build);

      // Add user cache context because this can change for every user.
      $build['#cache']['contexts'] = ['user'];

      return $build;
    }
    else {
      return ['#markup' => $this->t('No groups membership found.')];
    }

  }

  /**
   * Build output attaching libraris and cache settings.
   */
  private function buildContents($build) {
    $build[] = [
      '#attached' => [
        'library' => ['yasm/global', 'yasm/fontawesome', 'yasm/datatables'],
        'drupalSettings' => ['datatables' => ['locale' => $this->datatables->getLocale()]],
      ],
      '#cache' => ['max-age' => 3600],
    ];

    return $build;
  }

  /**
   * Build groups contents table from groups contents array.
   */
  private function buildGroupsContentsTable($groupsContents) {
    // Get all content types.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    // Create groups content table.
    $rows = [];
    foreach ($groupsContents as $group) {
      $row = [
        'type'     => $group['type'],
        'name'     => $group['name'],
        'nodes'    => $group['nodes'],
      ];
      // List all content types.
      foreach ($content_types as $key => $value) {
        $row[$key] = isset($group['by_type'][$key]) ? $group['by_type'][$key]['count'] : 0;
      }
      $rows[] = $row;
    }

    $labels = [
      $this->t('Type'),
      $this->t('Group'),
      $this->t('Nodes'),
    ];
    // All content types labels.
    foreach ($content_types as $content_type) {
      $labels[] = $content_type->label();
    }

    return YasmUtility::table($labels, $rows, 'groups_contents');
  }

  /**
   * Build groups members table from groups members array.
   */
  private function buildGroupsMembersTable($groupsMembers) {
    $groupsRoles = $this->groupsStatistics->getGroupRoles();
    $rows = [];
    foreach ($groupsMembers as $group) {
      $row = [
        'type'     => $group['type'],
        'name'     => $group['name'],
        'members'  => $group['members'],
      ];
      // List all group roles.
      foreach ($groupsRoles as $key => $value) {
        $row[] = isset($group['by_role'][$key]) ? $group['by_role'][$key]['count'] : 0;
      }
      $rows[] = $row;
    }
    $labels = [
      $this->t('Type'),
      $this->t('Group'),
      $this->t('Members'),
    ];
    // All group roles labels.
    foreach ($groupsRoles as $role) {
      $labels[] = $role;
    }

    return YasmUtility::table($labels, $rows, 'groups_members');
  }

  /**
   * Get groups content statistics.
   */
  private function getGroupsContents($groups) {
    $groups_stats = [];
    foreach ($groups as $group) {
      $g = ($group instanceof GroupMembership) ? $group->getGroup() : $group;
      if ($g instanceof Group) {
        $groups_stats[$g->id()] = [
          'name'    => $g->label(),
          'type'    => $g->getGroupType()->label(),
          'type_id' => $g->getGroupType()->id(),
          'nodes'   => $this->groupsStatistics->countNodes($g),
          'by_type' => $this->groupsStatistics->countNodesByType($g),
        ];
      }
    }

    return $groups_stats;
  }

  /**
   * Get groups members statistics.
   */
  private function getGroupsMembers($groups) {
    $groups_stats = [];
    foreach ($groups as $group) {
      $g = ($group instanceof GroupMembership) ? $group->getGroup() : $group;
      if ($g instanceof Group) {
        $groups_stats[$g->id()] = [
          'name'    => $g->label(),
          'type'    => $g->getGroupType()->label(),
          'type_id' => $g->getGroupType()->id(),
          'members' => $this->groupsStatistics->countMembers($g),
          'by_role' => $this->groupsStatistics->countMembersByRole($g),
        ];
      }
    }

    return $groups_stats;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, DatatablesInterface $datatables, GroupsStatisticsInterface $groups_statistics) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->datatables = $datatables;
    $this->groupsStatistics = $groups_statistics;

    // Conditional dependency injection is not working. Remove this when works.
    if ($this->moduleHandler->moduleExists('group')) {
      $this->setGroupMembershipLoader(\Drupal::service('group.membership_loader'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('yasm.datatables'),
      $container->get('yasm.groups_statistics')
    );
  }

  /**
   * Set group membership service for conditional depdendency injection.
   */
  public function setGroupMembershipLoader(GroupMembershipLoaderInterface $group_membership_loader) {
    $this->groupMembershipLoader = $group_membership_loader;
  }

}
