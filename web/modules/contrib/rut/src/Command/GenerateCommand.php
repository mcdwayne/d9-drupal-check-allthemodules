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
 * Class GenerateCommand.
 *
 * @package Drupal\rut
 */
class GenerateCommand extends Command {
  /**
   * {@inheritdoc}
   */
  protected function configure() {

    $this
          ->setName('rut:generate')
          ->setDescription($this->trans('command.rut.generate.description'))
          ->setHelp($this->trans('command.rut.generate.help'))
          ->addArgument('quantity', InputArgument::OPTIONAL, $this->trans('command.rut.generate.arguments.quantity'), 1)
          ->addOption('min', NULL, InputOption::VALUE_OPTIONAL, $this->trans('command.rut.generate.options.min'), 1)
          ->addOption('max', NULL, InputOption::VALUE_OPTIONAL, $this->trans('command.rut.generate.options.max'), 20000000);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $io = new DrupalStyle($input, $output);

    $quantity = (int) $input->getArgument('quantity');

    if ($quantity < 1) {
      $io->error($this->trans('command.rut.generate.errors.invalid-quantity'));

      return;
    }

    if ($quantity > 20) {
      $io->info($this->trans('command.rut.generate.messages.quantity-limit'));
      $quantity = 20;
    }

    $tableHeader = [
        'Rut',
        'DV',
        ' ',
        $this->trans('command.rut.generate.messages.formatted'),
    ];
    $tableRows = [];

    $min = (int) $input->getOption('min');
    $max = (int) $input->getOption('max');

    $io->success(
        sprintf($this->trans('command.rut.generate.messages.success'), $quantity)
    );

    for ($i = 0; $i < $quantity; $i++) {
      list($rut, $dv) = Rut::generateRut(FALSE, $min, $max);
      $tableRows[] = [
            $rut,
            $dv,
            ' ',
            Rut::formatterRut($rut, $dv),
        ];
    }

    $io->table($tableHeader, $tableRows, 'compact');
  }

}
