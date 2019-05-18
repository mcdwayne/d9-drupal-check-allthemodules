<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Trait BaseEntitySelect.
 *
 * @package Drupal\drd\Command
 */
trait BaseEntitySelect {

  /**
   * Load and configure service to select entities.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input interface from console.
   *
   * @return \Drupal\drd\Entities
   *   DRD service for entity selction.
   */
  protected function getService(InputInterface $input) {
    return \Drupal::service('drd.entities')
      ->setTag($input->getOption('tag'))
      ->setHost($input->getOption('host'))
      ->setHostId($input->getOption('host-id'))
      ->setCore($input->getOption('core'))
      ->setCoreId($input->getOption('core-id'))
      ->setDomain($input->getOption('domain'))
      ->setDomainId($input->getOption('domain-id'));
  }

  /**
   * Add configuration for commands that select entities.
   */
  protected function configureSelection() {
    /** @var \Symfony\Component\Console\Command\Command $this */
    $this
      ->addOption(
        'tag',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.tag')
      )
      ->addOption(
        'host',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.host')
      )
      ->addOption(
        'host-id',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.host-id')
      )
      ->addOption(
        'core',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.core')
      )
      ->addOption(
        'core-id',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.core-id')
      )
      ->addOption(
        'domain',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.domain')
      )
      ->addOption(
        'domain-id',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.actions.remote.arguments.domain-id')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function addSelectionAsArguments(ActionBaseInterface $action, InputInterface $input) {
    foreach ([
      'tag',
      'host',
      'host-id',
      'core',
      'core-id',
      'domain',
      'domain-id',
    ] as $item) {
      $action->setActionArgument($item, $input->getOption($item));
    }
  }

}
