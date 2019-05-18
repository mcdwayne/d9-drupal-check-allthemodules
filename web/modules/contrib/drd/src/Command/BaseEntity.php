<?php

namespace Drupal\drd\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseEntityRemote;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseEntity.
 *
 * @package Drupal\drd
 */
abstract class BaseEntity extends Base {

  use BaseEntitySelect;

  /**
   * Callback to query all DRD entities according to CLI arguments.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Source of arguments.
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   Output destination.
   * @param \Drupal\drd\Plugin\Action\BaseEntityRemote $action
   *   Action to be executed.
   *
   * @return bool|\Drupal\drd\Entity\BaseInterface[]
   *   List of entities or FALSE if none was found.
   */
  abstract protected function getEntities(InputInterface $input, DrupalStyle $io, BaseEntityRemote $action);

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->configureSelection();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var \Drupal\drd\Plugin\Action\BaseEntityRemote $action */
    $action = parent::execute($input, $output);
    $io = new DrupalStyle($input, $output);
    if (empty($action)) {
      $io->error('No action found!');
      return;
    }

    $entities = $this->getEntities($input, $io, $action);
    if (!isset($entities)) {
      $io->error('No valid action found!');
      return;
    }
    if (empty($entities)) {
      $io->error('No entities found!');
      return;
    }

    $io->info('Executing ' . $action->getPluginDefinition()['label']);
    foreach ($entities as $entity) {
      /** @var \Drupal\drd\Entity\BaseInterface $entity */
      $io->info('- on id ' . $entity->id() . ': ' . $entity->getName());
      if ($action->executeAction($entity)) {
        $io->success('  ok!');
      }
      else {
        $io->error('  failure!');
      }

      $output = $action->getOutput();
      if ($output) {
        foreach ($output as $value) {
          $io->info('  ' . $value);
        }
      }
    }

    $io->info('Completed!');
  }

}
