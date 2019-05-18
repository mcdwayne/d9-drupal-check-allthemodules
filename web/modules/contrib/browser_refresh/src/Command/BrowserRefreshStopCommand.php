<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\Command\BrowserRefreshStopCommand.
 */

namespace Drupal\browser_refresh\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BrowserRefreshStopCommand.
 *
 * @package Drupal\browser_refresh
 */
class BrowserRefreshStopCommand extends BrowserRefreshCommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('browser-refresh:stop');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getService($output)->stop();
  }

}
