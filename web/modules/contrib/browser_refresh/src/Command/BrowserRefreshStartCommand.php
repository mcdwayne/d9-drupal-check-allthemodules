<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\Command\BrowserRefreshStartCommand.
 */

namespace Drupal\browser_refresh\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BrowserRefreshStartCommand.
 *
 * @package Drupal\browser_refresh
 */
class BrowserRefreshStartCommand extends BrowserRefreshCommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('browser-refresh:start');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getService($output)->start();
  }

}
