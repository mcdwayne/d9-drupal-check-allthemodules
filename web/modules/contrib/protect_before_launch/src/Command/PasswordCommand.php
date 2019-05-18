<?php

namespace Drupal\protect_before_launch\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class PasswordCommand.
 *
 * @package Drupal\protect_before_launch
 *
 * @DrupalCommand (
 *     extension="protect_before_launch",
 *     extensionType="module"
 * )
 */
class PasswordCommand extends AbstractCommand {

  /**
   * Password argument.
   */
  const ARGUMENT_PASSWORD = 'password';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('protect_before_launch:password')
      ->addArgument(self::ARGUMENT_PASSWORD, InputArgument::OPTIONAL)
      ->setDescription($this->trans('commands.protect_before_launch.password.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $password = $input->getArgument(self::ARGUMENT_PASSWORD);
    if (NULL == $password) {
      $password = $this->getIo()->ask($this->trans('commands.protect_before_launch.password.messages.question'));
    }

    $this->getConfig()->setPassword($password);

    $this->getIo()->info(sprintf($this->trans('commands.protect_before_launch.password.messages.success'), $password));
  }

}
