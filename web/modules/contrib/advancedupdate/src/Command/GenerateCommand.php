<?php

namespace Drupal\advanced_update\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\advanced_update\UpdateGenerator;

/**
 * Class GenerateCommand.
 *
 * @package Drupal\advanced_update
 */
class GenerateCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:advancedupdate')
      ->setAliases(array('generate:adup'))
      ->setDescription($this->trans('command.advanced_update.generate.description'))
      ->setHelp('This command allow you to create a new update class in your module');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $io->simple('');
    $io->simple($this->trans('// Welcome to the Drupal advanced update generator'));
    $io->simple('');

    $modules = array_keys(system_get_info('module'));
    $module_name = $io->choiceNoList('Module name to generate a new update', $modules, $this->getModule(), FALSE);

    $descritpion = $io->askEmpty('Describe your functionality');

    $generator = new UpdateGenerator();
    $generator->generate($module_name, $descritpion);
  }

}
