<?php

namespace Drupal\protect_before_launch\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\protect_before_launch\Configuration;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class UsernameCommand.
 *
 * @package Drupal\protect_before_launch
 *
 * @DrupalCommand (
 *     extension="protect_before_launch",
 *     extensionType="module"
 * )
 */
class UsernameCommand extends AbstractCommand {

  /**
   * Username argument.
   */
  const ARGUMENT_USERNAME = 'username';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('protect_before_launch:username')
      ->addArgument(self::ARGUMENT_USERNAME, InputArgument::OPTIONAL)
      ->setDescription($this->trans('commands.protect_before_launch.username.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $username = $input->getArgument(self::ARGUMENT_USERNAME);
    if (empty($username)) {
      $username = $this->getIo()->ask($this->trans('commands.protect_before_launch.username.messages.question'));
    }

    $this->getConfig()->setUsername($username);

    $this->getIo()->info(sprintf($this->trans('commands.protect_before_launch.username.messages.success'), $username));
  }

}
