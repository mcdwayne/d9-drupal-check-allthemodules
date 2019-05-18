<?php

namespace Drupal\entity_pilot_git\Command;

use Drupal\Core\Url;
use Drupal\entity_pilot\Batch\AirTrafficController;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot\Entity\Account;
use Drupal\entity_pilot\Entity\Arrival;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot_git\GitTransport;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class ImportCommand.
 *
 * @package Drupal\entity_pilot_git
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="entity_pilot_git",
 *     extensionType="module"
 * )
 */
class ImportCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('entity_pilot_git:import')
      ->setDescription($this->trans('commands.entity_pilot_git.import.description'))
      ->addArgument(
        'account',
        InputArgument::REQUIRED,
        $this->trans('commands.entity_pilot_git.import.arguments.account')
      )
      ->addOption(
        'land',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.entity_pilot_git.import.options.land')
      )
      ->addOption(
        'manifest',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.entity_pilot_git.import.options.manifest')
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

    /** @var \Drupal\Component\Serialization\Json $serializer */
    $serializer = $this->get('serialization.json');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->get('config.factory');
    /** @var \Drupal\hal\LinkManager\TypeLinkManagerInterface $link_manager */
    $link_manager = $this->get('hal.link_manager.type');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->get('entity_type.manager');

    $transport = new GitTransport($serializer, $config_factory, $link_manager, $entity_type_manager);

    if ($manifest_id = $input->getOption('manifest')) {
      $flight = $transport->getFlight($manifest_id, $account_object);
      if (empty($flight)) {
        throw new \Exception(sprintf($this->trans('commands.entity_pilot_git.errors.flight_not_found'), $manifest_id));
      }
    }
    else {
      // Get the latest flight.
      $flights = $transport->queryFlights($account_object, '', 1, -1);
      if (empty($flights)) {
        throw new \Exception($this->trans('commands.entity_pilot_git.errors.no_flights'));
      }
      $flight = array_pop($flights);
    }

    /** @var \Drupal\entity_pilot\ArrivalInterface $arrival */
    $arrival = Arrival::create(array(
      'account' => $account,
    ));
    $arrival->setInfo($flight->getInfo());
    $context = array();

    $site = Url::fromRoute('<front>', array(), array('absolute' => TRUE))->toString();
    AirTrafficController::land($arrival, $flight, $site, $context);

    if ($input->getOption('land')) {
      /** @var \Drupal\entity_pilot\CustomsInterface $customs */
      $customs = $this->get('entity_pilot.customs');
      // Customs::screen returns a sorted array of entities by dependency.
      $entities = $customs->screen($arrival);
      // Merge the passengers into an array of sorted uuids to get a sorted list
      // of passengers.
      $sorted_uuids = array_flip(array_keys($entities));
      $sorted_passengers = array_merge($sorted_uuids, $arrival->getPassengers());

      foreach ($sorted_passengers as $uuid => $passenger) {
        // Skip anonymous user for now.
        // TODO: Remove once we fix exporting of anon user.
        if ($entities[$uuid]->getEntityTypeId() == 'user' && empty($entities[$uuid]->getUsername())) {
          continue;
        }

        $customs->approvePassenger($uuid);
      }

      $arrival->setStatus(FlightInterface::STATUS_LANDED)->save();
      $output->writeln(sprintf($this->trans('commands.entity_pilot_git.notices.landed'), $arrival->label()));
    }
    else {
      $output->writeln(sprintf($this->trans('commands.entity_pilot_git.notices.approval'), $arrival->label()));
    }
  }

  /**
   * Sorts flights by timestamp so we can get the latest one.
   *
   * The id of a flight is the timestamp is was created.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $a
   *   The first flight.
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $b
   *   The second flight.
   *
   * @return int
   *   An integer for uasort.
   */
  private function sortByFlightId(FlightManifestInterface $a, FlightManifestInterface $b) {
    return $a->getRemoteId() - $b->getRemoteId();
  }

}
