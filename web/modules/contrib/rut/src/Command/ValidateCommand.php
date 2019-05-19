<?php

namespace Drupal\rut\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\rut\Rut;

/**
 * Class ValidateCommand.
 *
 * @package Drupal\rut
 */
class ValidateCommand extends Command {
  /**
   * {@inheritdoc}
   */
  protected function configure() {

    $this
          ->setName('rut:validate')
          ->setDescription($this->trans('command.rut.validate.description'))
          ->setHelp($this->trans('command.rut.validate.help'))
          ->addArgument('rut', InputArgument::REQUIRED, $this->trans('command.rut.validate.arguments.rut'))
          ->addArgument('dv', InputArgument::OPTIONAL, $this->trans('command.rut.validate.arguments.dv'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $io = new DrupalStyle($input, $output);

    $rut = $input->getArgument('rut');
    $dv = $input->getArgument('dv');

    if ($dv == NULL) {
      list($rut, $dv) = Rut::separateRut($rut);
    }

    if (Rut::validateRut($rut, $dv)) {
      $io->success(
            sprintf($this->trans('command.rut.validate.messages.success'), Rut::formatterRut($rut, $dv))
        );
    }
    else {
      $io->error(
            sprintf($this->trans('command.rut.validate.messages.error'), $rut, $dv)
        );
    }
  }

}
