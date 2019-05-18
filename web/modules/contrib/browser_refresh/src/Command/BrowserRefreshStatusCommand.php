<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\Command\BrowserRefreshStatusCommand.
 */

namespace Drupal\browser_refresh\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BrowserRefreshStatusCommand.
 *
 * @package Drupal\browser_refresh
 */
class BrowserRefreshStatusCommand extends BrowserRefreshCommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('browser-refresh:status');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getService($output)->isActive(TRUE);
  }

}
