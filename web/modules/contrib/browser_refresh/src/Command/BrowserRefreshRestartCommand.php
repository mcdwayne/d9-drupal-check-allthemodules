<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\Command\BrowserRefreshRestartCommand.
 */

namespace Drupal\browser_refresh\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BrowserRefreshRestartCommand.
 *
 * @package Drupal\browser_refresh
 */
class BrowserRefreshRestartCommand extends BrowserRefreshCommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('browser-refresh:restart');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getService($output)->restart();
  }

}
