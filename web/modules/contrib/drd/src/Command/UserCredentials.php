<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class UserCredentials.
 *
 * @package Drupal\drd
 */
class UserCredentials extends BaseDomain {

  /**
   * Construct the UserCredentials command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_user_credentials';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:user:credentials')
      ->setDescription($this->trans('commands.drd.action.user.credentials.description'))
      ->addArgument(
        'uid',
        InputArgument::OPTIONAL,
        $this->trans('commands.drd.action.user.credentials.arguments.uid')
      )
      ->addOption(
        'username',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.user.credentials.arguments.username')
      )
      ->addOption(
        'password',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.user.credentials.arguments.password')
      )
      ->addOption(
        'status',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.user.credentials.arguments.status')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    if ($uid = $input->getArgument('uid')) {
      $action->setActionArgument('uid', $uid);
    }
    if ($username = $input->getOption('username')) {
      $action->setActionArgument('username', $username);
    }
    if ($password = $input->getOption('password')) {
      $action->setActionArgument('password', $password);
    }
    $status = $input->getOption('status');
    if (isset($status)) {
      $action->setActionArgument('status', $status);
    }
  }

}
