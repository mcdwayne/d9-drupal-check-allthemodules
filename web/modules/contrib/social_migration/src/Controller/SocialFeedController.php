<?php

namespace Drupal\social_migration\Controller;

use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialFeedController.
 *
 * This controller handles the list page for all social providers.
 */
class SocialFeedController extends ControllerBase {

  /**
   * The Social Media provider (facebook, twitter, instagram).
   *
   * @var string
   */
  protected $socialProvider;

  /**
   * The name of the migation group for social feeds.
   *
   * @var array
   */
  protected $socialGroupIds = [
    'facebook' => 'social_migration_facebook_feeds_group',
    'instagram' => 'social_migration_instagram_feeds_group',
    'twitter' => 'social_migration_twitter_feeds_group',
  ];

  /**
   * The list routes for social feeds.
   *
   * @var array
   */
  protected $listRouteNames = [
    'facebook' => 'social_migration.facebook.list',
    'instagram' => 'social_migration.instagram.list',
    'twitter' => 'social_migration.twitter.list',
  ];

  /**
   * The edit routes for social feeds.
   *
   * @var array
   */
  protected $editRouteNames = [
    'facebook' => 'social_migration.facebook.edit',
    'instagram' => 'social_migration.instagram.edit',
    'twitter' => 'social_migration.twitter.edit',
  ];

  /**
   * The delete routes for social feeds.
   *
   * @var array
   */
  protected $deleteRouteNames = [
    'facebook' => 'social_migration.facebook.delete',
    'instagram' => 'social_migration.instagram.delete',
    'twitter' => 'social_migration.twitter.delete',
  ];

  /**
   * The route to import any feed.
   *
   * @var string
   */
  protected $runRouteName = 'social_migration.run';

  /**
   * The route to roll back any feed.
   *
   * @var string
   */
  protected $rollbackRouteName = 'social_migration.rollback';

  /**
   * The route to reset a migration's status.
   *
   * @var string
   */
  protected $resetStatusRouteName = 'social_migration.reset_status';

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\migrate\Plugin\MigrationPluginManager definition.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new SocialFeedController object.
   */
  public function __construct(
    QueryFactory $entity_query,
    EntityTypeManager $entity_type_manager,
    MigrationPluginManager $migration_plugin_manager
  ) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Route for social_migration.[provider].list.
   *
   * @param string $social_media_provider
   *   The social media provider for this route.
   */
  public function listMigrations($social_media_provider = NULL) {
    $this->socialProvider = $social_media_provider;
    $groupId = $this->socialGroupIds[$social_media_provider];

    $header = [
      'property_name' => $this->t('Property Name'),
      'feed_name' => $this->t('Feed Name'),
      'cron_enabled' => $this->t('Cron Enabled/Disabled'),
      'status' => $this->t('Status'),
      'operations' => $this->t('Operations'),
    ];

    $query = $this->entityQuery->get('migration')
      ->condition('migration_group', $groupId);
    $results = $query->execute();
    $migrations = $this->entityTypeManager
      ->getStorage('migration')
      ->loadMultiple($results);

    $rows = [];
    foreach ($migrations as $migrationId => $migration) {
      $propertyName = $this->getPropertyName($migration);
      $status = $this->migrationPluginManager->createInstance($migrationId)->getStatusLabel();
      $cronEnabled = isset($migration->migration_tags['cron_enabled']) ? $migration->migration_tags['cron_enabled'] : TRUE;

      $rows[$migrationId] = [
        'property_name' => $propertyName,
        'feed_name' => $migration->label(),
        'cron_enabled' => $cronEnabled ? 'Enabled' : 'Disabled',
        'status' => $status,
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute($this->editRouteNames[$social_media_provider], ['migration' => $migrationId]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute($this->deleteRouteNames[$social_media_provider], ['migration' => $migrationId]),
              ],
              'run' => [
                'title' => $this->t('Run'),
                'url' => Url::fromRoute($this->runRouteName, [
                  'provider' => $social_media_provider,
                  'migration' => $migrationId,
                ]),
              ],
              'rollback' => [
                'title' => $this->t('Roll Back'),
                'url' => Url::fromRoute($this->rollbackRouteName, [
                  'provider' => $social_media_provider,
                  'migration' => $migrationId,
                ]),
              ],
              'reset_status' => [
                'title' => $this->t('Reset Status'),
                'url' => Url::fromRoute($this->resetStatusRouteName, [
                  'provider' => $social_media_provider,
                  'migration' => $migrationId,
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    $form['migrations'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No %provider feeds found', ['%provider' => $social_media_provider]),
    ];

    return $form;
  }

  /**
   * Route for social_migration.run.
   *
   * @param string $provider
   *   The social media provider.
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration to run.
   */
  public function runMigration($provider = '', Migration $migration = NULL) {
    $this->doRunMigration($migration);
    return $this->redirect($this->listRouteNames[$provider]);
  }

  /**
   * Route for social_migration.run_group.
   *
   * @param string $provider
   *   The social media provider.
   * @param \Drupal\migrate_plus\Entity\MigrationGroup $migration_group
   *   The migration group to run.
   */
  public function runMigrationGroup($provider = '', MigrationGroup $migration_group = NULL) {
    $migrationGroupId = $migration_group->id();
    $migrationIds = $this->entityQuery->get('migration')
      ->condition('migration_group', $migrationGroupId)
      ->execute();

    $migrations = $this->entityTypeManager->getStorage('migration')->loadMultiple($migrationIds);
    array_walk($migrations, [$this, 'doRunMigration']);

    return $this->redirect($this->listRouteNames[$provider]);
  }

  /**
   * Route for social_migration.rollback.
   *
   * @param string $provider
   *   The social media provider.
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration to roll back.
   */
  public function rollbackMigration($provider = '', Migration $migration = NULL) {
    $this->doRollbackMigration($migration);
    return $this->redirect($this->listRouteNames[$provider]);
  }

  /**
   * Route for social_migration.rollback_group.
   *
   * @param string $provider
   *   The social media provider.
   * @param \Drupal\migrate_plus\Entity\MigrationGroup $migration_group
   *   The migration group to roll back.
   */
  public function rollbackMigrationGroup($provider = '', MigrationGroup $migration_group = NULL) {
    $migrationGroupId = $migration_group->id();
    $migrationIds = $this->entityQuery->get('migration')
      ->condition('migration_group', $migrationGroupId)
      ->execute();

    $migrations = $this->entityTypeManager->getStorage('migration')->loadMultiple($migrationIds);
    array_walk($migrations, [$this, 'doRollbackMigration']);

    return $this->redirect($this->listRouteNames[$provider]);
  }

  /**
   * Route for social_migration.reset_status.
   *
   * @param string $provider
   *   The social media provider.
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration to reset status.
   */
  public function resetMigrationStatus($provider = '', Migration $migration = NULL) {
    $this->doResetMigrationStatus($migration);
    return $this->redirect($this->listRouteNames[$provider]);
  }

  /**
   * Run a migration.
   *
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration to run.
   */
  protected function doRunMigration(Migration $migration) {
    $logger = new MigrateMessage();
    $migrationInterface = $this->migrationPluginManager->createInstance($migration->id());
    $executable = new MigrateExecutable($migrationInterface, $logger);

    try {
      $result = $executable->import();
      $count = $executable->getFailedCount();

      if ($count) {
        drupal_set_message($this->t('Migration warning: %count failed.', ['%count' => $count]), 'warning');
      }
      else {
        drupal_set_message($this->t('Migration succeeded.'));
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Migration exception:') . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Roll back a migration.
   *
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration to roll back.
   */
  protected function doRollbackMigration(Migration $migration) {
    $logger = new MigrateMessage();
    $migrationInterface = $this->migrationPluginManager->createInstance($migration->id());
    $executable = new MigrateExecutable($migrationInterface, $logger);
    $executable->rollback();
    if ($count = $executable->getFailedCount()) {
      drupal_set_message($this->t('Migration warning: %count failed.', ['%count' => $count]), 'warning');
    }
    else {
      drupal_set_message($this->t('Migration rolled back.'));
    }
  }

  /**
   * Reset a migration status.
   *
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration to reset status.
   */
  protected function doResetMigrationStatus(Migration $migration) {
    $migrationId = $migration->id();
    $label = $migration->label();
    if ($migrationInterface = $this->migrationPluginManager->createInstance($migrationId)) {
      $status = $migrationInterface->getStatus();
      if ($status == MigrationInterface::STATUS_IDLE) {
        drupal_set_message($this->t('Migration %label is already idle.', ['%label' => $label]));
      }
      else {
        $migrationInterface->setStatus(MigrationInterface::STATUS_IDLE);
        drupal_set_message($this->t('Migration %label reset to idle.', ['%label' => $label]));
      }
    }

  }

  /**
   * Return the property name of the migration.
   *
   * @param \Drupal\migrate_plus\Entity\Migration $migration
   *   The migration from which to retrieve the property name.
   *
   * @return string
   *   The property name for the migration.
   */
  protected function getPropertyName(Migration $migration) {
    switch ($this->socialProvider) {
      case 'facebook':
        $url = $migration->source['urls'];
        if (preg_match('/\/v[0-9\.]+\/([\w\d\.]+)\/.*/', $url, $matches) === 1) {
          return $matches[1];
        }
        break;

      case 'twitter':
        $url = $migration->source['urls'];
        if (preg_match('/screen_name=(\w+)/', $url, $matches) === 1) {
          return $matches[1];
        }
        break;

      case 'instagram':
        $tags = $migration->migration_tags;
        if (isset($tags['account']) && !empty($tags['account'])) {
          return $tags['account'];
        }
        break;
    }

    return '(undefined)';
  }

}
