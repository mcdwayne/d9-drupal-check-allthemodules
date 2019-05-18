<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\Command\BrowserRefreshCommandBase.
 */

namespace Drupal\browser_refresh\Command;

use Drupal\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\browser_refresh\BrowserRefreshServiceInterface;

/**
 * Class BrowserRefreshCommandBase.
 *
 * @package Drupal\browser_refresh
 */
abstract class BrowserRefreshCommandBase extends Command {

  /**
   * @param OutputInterface $output
   * @return BrowserRefreshServiceInterface
   */
  protected function getService($output) {
    return \Drupal::service('browser_refresh.service')->setOutput($output);
  }

}
