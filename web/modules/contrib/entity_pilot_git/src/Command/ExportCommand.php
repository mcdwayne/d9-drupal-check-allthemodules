<?php

namespace Drupal\entity_pilot_git\Command;

use Drupal\user\Entity\User;
use Drupal\entity_pilot\Batch\AirTrafficController;
use Drupal\entity_pilot\Entity\Account;
use Drupal\entity_pilot\Entity\Departure;
use Drupal\entity_pilot\FlightInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class ExportCommand.
 *
 * @package Drupal\entity_pilot_git
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="entity_pilot_git",
 *     extensionType="module"
 * )
 */
class ExportCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('entity_pilot_git:export')
      ->setDescription($this->trans('commands.entity_pilot_git.export.description'))
      ->addArgument(
        'account',
        InputArgument::REQUIRED,
        $this->trans('commands.entity_pilot_git.export.arguments.account')
      )
      ->addArgument(
        'entity_type',
        InputArgument::OPTIONAL,
        $this->trans('commands.entity_pilot_git.export.arguments.entity_type')
      )
      ->addOption(
        'from-date',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.entity_pilot_git.export.options.from_date')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $account = $input->getArgument('account');
    /** @var \Drupal\entity_pilot\Entity\Account $account_object */
    $account_object = Account::load($account);
    if (empty($account_object)) {
      throw new \Exception(sprintf($this->trans('commands.entity_pilot_git.errors.no_account'), $account));
    }

    // Run as user/1 to permit all user fields in the export.
    $this->get('current_user')->setAccount(User::load(1));

    $from_date = $input->getOption('from-date');
    $timestamp = 0;
    if (!empty($from_date) && is_numeric($from_date)) {
      $timestamp = $from_date;
    }
    elseif (!empty($from_date) && !$timestamp = strtotime($from_date)) {
      throw new \Exception(sprintf('%s is not a valid date', $from_date));
    }

    if (!$entity_type = $input->getArgument('entity_type')) {
      // Get all content entity types.
      /** @var \Drupal\Core\Entity\EntityManager $entity_manager */
      $entity_manager = $this->get('entity.manager');
      $labels = $entity_manager->getEntityTypeLabels(TRUE);
      $entity_types = array_keys($labels['Content']);
    }
    else {
      $entity_types = [$entity_type];
    }
    // We don't want to export these types.
    $skip_types = $this->get('config.factory')->get('entity_pilot_git.settings')->get('skip_entity_types') ?: [];
    $entity_types = array_diff($entity_types, array_keys(array_filter($skip_types)));

    $passengers = [];

    /** @var \Drupal\entity_pilot_git\EntityOperationsInterface $entity_operations */
    $entity_operations = $this->get('entity_pilot_git.entity_operations');
    if ($timestamp && !$entity_operations->checkForUpdates($timestamp, $entity_types)) {
      throw new \Exception(sprintf($this->trans('commands.entity_pilot_git.errors.no_updates'), date('d F Y H:i', $timestamp), implode(', ', $entity_types)));
    }

    foreach ($entity_types as $entity_type) {
      $passengers = array_merge($passengers, $this->gatherPassengers($output, $entity_type, $timestamp));
    }

    if (empty($passengers)) {
      throw new \Exception(sprintf($this->trans('commands.entity_pilot_git.errors.no_content'), implode(', ', $entity_types)));
    }

    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = $this->get('date.formatter');

    /** @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = Departure::create(array(
      'account' => $account,
    ));

    $date = $date_formatter->format(REQUEST_TIME, '', 'd/m/Y H:i:s');
    $departure->setInfo('Console export: ' . $date)
      ->setRevisionLog('Newly created: ' . $date)
      ->setStatus(FlightInterface::STATUS_PENDING);

    $departure->setPassengers($passengers);
    $departure->save();

    $context = array();
    AirTrafficController::takeoff($departure, $context);
  }

  /**
   * Gathers passengers for the departure.
   *
   * @param OutputInterface $output
   *   The output interface.
   * @param string $entity_type_id
   *   The entity type.
   * @param int $from_date
   *   Timestamp to check for content from.
   *
   * @return array
   *   An array of passengers.
   */
  protected function gatherPassengers(OutputInterface $output, $entity_type_id, $from_date) {
    $passengers = array();
    /** @var \Drupal\entity_pilot_git\EntityOperationsInterface $entity_operations */
    $entity_operations = $this->get('entity_pilot_git.entity_operations');
    $entity_ids = $entity_operations->getEntitiesFromDate($entity_type_id, $from_date);

    $output->writeln(sprintf($this->trans('commands.entity_pilot_git.notices.exporting'), count($entity_ids), $entity_type_id));

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $this->get('entity.manager')
      ->getStorage($entity_type_id);

    // TODO: Speed this up with raw sql instead of loading entities.
    $entities = $entity_storage->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      // Don't export anonymous as it is created during site install.
      // Todo: this should be a flag? We only need this because site-install
      // creates a user each time, others may want to export anonymous.
      if ($entity_type_id == 'user' && $entity->id() == 0) {
        continue;
      }
      $passengers[] = [
        'target_type' => $entity_type_id,
        'target_id' => $entity->id(),
      ];
    }
    return $passengers;
  }

}
