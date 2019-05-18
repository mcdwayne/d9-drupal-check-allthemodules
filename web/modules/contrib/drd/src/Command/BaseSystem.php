<?php

namespace Drupal\drd\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseGlobalInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseSystem.
 *
 * @package Drupal\drd
 */
abstract class BaseSystem extends Base {

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $action = parent::execute($input, $output);
    $io = new DrupalStyle($input, $output);

    if (empty($action) || !($action instanceof BaseGlobalInterface)) {
      $io->error('No valid local action!');
      return FALSE;
    }

    $io->info('Executing ' . $action->getPluginDefinition()['label']);
    if ($result = $action->executeAction()) {
      $io->success('  ok!');
    }
    else {
      $io->error('  failure!');
    }

    $io->info('Completed!');
    return $result;
  }

}
