<?php

namespace Drupal\protect_before_launch\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\protect_before_launch\Configuration;

/**
 * Class AbstractCommand.
 *
 * @package Drupal\protect_before_launch\Command
 */
abstract class AbstractCommand extends Command {

  use CommandTrait;

  /**
   * Configuration.
   *
   * @var \Drupal\protect_before_launch\Configuration
   */
  private $config;

  /**
   * Drupal Console IO Style.
   *
   * @var \Drupal\Console\Core\Style\DrupalStyle
   */
  private $io;

  /**
   * Configuration.
   *
   * @param \Drupal\protect_before_launch\Configuration $config
   *   Configuration.
   */
  public function __construct(Configuration $config) {
    $this->config = $config;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);
  }

  /**
   * Return Configuration.
   *
   * @return \Drupal\protect_before_launch\Configuration
   *   Configuration.
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Return Drupal IO Style.
   *
   * @return \Drupal\Console\Core\Style\DrupalStyle
   *   Drupal IO Style
   */
  public function getIo() {
    return $this->io;
  }

}
