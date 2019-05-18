<?php

namespace Drupal\client_config_care\Commands;

use Drupal\client_config_care\Deactivator;
use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\client_config_care\Fixture\EntityCreator;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ClientConfigCareCommands extends DrushCommands {

	/**
	 * @var EntityCreator
	 */
	private $entityCreator;

  /**
   * @var EntityStorageInterface
   */
  private $configBlockerEntityStorage;

  /**
   * @var DateFormatter
   */
  private $dateFormatter;

  public function __construct(EntityCreator $entityCreator, EntityTypeManagerInterface $entityTypeManager, DateFormatter $dateFormatter)
	{
	  parent::__construct();
		$this->entityCreator = $entityCreator;
    $this->configBlockerEntityStorage = $entityTypeManager->getStorage(ConfigBlockerEntity::ENTITY_ID);
    $this->dateFormatter = $dateFormatter;
  }

	/**
   * Generates fixture config blocker entities for testing and development purpose.
   * @command client_config_care:generate_fixtures
   * @aliases ccc:gf
   */
  public function generateFixtures(): void {

		$this->entityCreator->createEntities();

    $this->logger()->success(dt('Fixtures created.'));
  }

  /**
   * Deletes all config blockers.
   * @command client_config_care:delete_all_blockers
   * @aliases ccc:dab
   */
  public function deleteAllBlockers(): void {
    $allEntities =  $this->configBlockerEntityStorage->loadMultiple();
    if ($this->io()->confirm(dt('You are about to delete all ') . count($allEntities) . dt(' blockers.') . ' Do you want to continue?')) {
      $this->configBlockerEntityStorage->delete($allEntities);
      $this->io()->comment(dt('Done.'));
    }
  }

  /**
   * Shows all available config blockers.
   * @command client_config_care:show_all_blockers
   * @aliases ccc:sab
   */
  public function showAllBlockers(): void {
    $allEntities =  $this->configBlockerEntityStorage->loadMultiple();

    if (empty($allEntities)) {
      $this->logger()->notice('No config blocker entities are existing.');
      return;
    }

    $output = 'The following config blocker entities are existing:' . PHP_EOL .
    'Name                 | Revision user        | Latest revision date' . PHP_EOL;


    /**
     * @var ConfigBlockerEntity $entity
     */
    foreach ($allEntities as $entity) {
      $output .= str_pad($entity->getName(), 20) . ' | ' .
        str_pad($entity->getRevisionUser()->getUsername(), 20) . ' | ' .
        $this->dateFormatter->format($entity->getCreatedTime()) . PHP_EOL;
    }

    $this->logger()->notice($output);
  }

  /**
   * Deletes a specific config blocker by config name.
   * @command client_config_care:delete_config_blocker_by_name
   * @aliases ccc:dbn
   */
  public function deleteConfigBlockerByName(string $name): void {
    $entities =  $this->configBlockerEntityStorage->loadByProperties(['name' => $name]);
    if (reset($entities) instanceof ConfigBlockerEntity) {
      $this->configBlockerEntityStorage->delete($entities);
      $this->logger()->notice("Deleted config blocker with name '$name'");
    }
  }

  /**
   * Shows if Client Config Care is activated. It is recommended to disable Client Config Care on development
   * environments.
   * @command client_config_care:is_activated
   * @aliases ccc:ia
   */
  public function isActivated(): void {
    /**
     * @var Deactivator $deactivator
     */
    $deactivator = \Drupal::service('client_config_care.deactivator');

    if ($deactivator->isDeactivated()) {
      $this->io()->comment(dt('[Deactivated] Client Config Care is currently deactivated. No configs are blocked and no configs blockers will be created. You can activate Client Config Care in your `settings.php` file. Check the `README.md` file for more info.'));
    } elseif ($deactivator->isNotDeactivated()) {
      $this->io()->comment(dt('[Activated] Client Config Care is currently activated. Configs are blocked by any existing config blocker entities. You can deactivate Client Config Care in your `settings.php` file. Check the `README.md` file for more info.'));

    }
  }

}
