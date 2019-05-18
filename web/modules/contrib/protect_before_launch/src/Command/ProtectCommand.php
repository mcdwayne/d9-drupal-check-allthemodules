<?php

namespace Drupal\protect_before_launch\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\protect_before_launch\Configuration;

/**
 * Class ProtectCommand.
 *
 * @package Drupal\protect_before_launch
 *
 * @DrupalCommand (
 *     extension="protect_before_launch",
 *     extensionType="module"
 * )
 */
class ProtectCommand extends AbstractCommand {

  /**
   * Protect argument.
   */
  const ARGUMENT_PROTECT = 'protect';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('protect_before_launch:protect')
      ->setAliases(['protect_before_launch:enabled'])
      ->addArgument(self::ARGUMENT_PROTECT, InputArgument::OPTIONAL)
      ->setDescription($this->trans('commands.protect_before_launch.protect.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $status = $input->getArgument(self::ARGUMENT_PROTECT);
    $options = [
      'disable', 'disabled',
      'enable', 'enabled',
      'env_enabled', 'env_enable',
      'enable_env', 'enabled_env',
      'env', 'environment'
    ];
    if (!in_array($status, $options)) {
      $status = $this->getIo()->choice($this->trans('commands.protect_before_launch.protect.messages.question'), [
        'disabled',
        'enabled',
        'environment'
      ]);
    }

    $this->getConfig()->setProtect($this->getStatusConfigCode($status));

    $this->getIo()->info(sprintf($this->trans('commands.protect_before_launch.protect.messages.success'), $status));
  }

  /**
   * Convert string to configuration constant.
   *
   * @param string $status
   *   User input.
   *
   * @return int
   *   Configuration constant.
   */
  private function getStatusConfigCode($status) {
    switch ($status) {
      case 'disable':
      case 'disabled':
        return Configuration::PROTECT_DISABLED;

      case 'enable':
      case 'enabled':
        return Configuration::PROTECT_ENABLED;

      default:
        return Configuration::PROTECT_ENV_ENABLED;
    }
  }

}
