<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\Base as ActionBase;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Drupal\user\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Base.
 *
 * @package Drupal\drd
 */
abstract class Base extends Command {

  /**
   * ID of this action plugin.
   *
   * @var string
   */
  protected $actionKey;

  /**
   * Logging service for console output.
   *
   * @var \Drupal\drd\Logging
   */
  protected $logger;

  use CommandTrait;
  use ModuleTrait;
  use FormTrait;
  use ConfirmationTrait;

  /**
   * {@inheritdoc}
   */
  public function run(InputInterface $input, OutputInterface $output) {
    $result = parent::run($input, $output);

    /* @var \Drupal\drd\QueueManager $q */
    $q = \Drupal::service('queue.drd');
    $q->processAll();

    return $result;
  }

  /**
   * Change current session to user 1.
   */
  protected function promoteUser() {
    \Drupal::currentUser()->setAccount(User::load(1));
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $this->logger = \Drupal::service('drd.logging');
    $this->logger->setIo($io);
    if ($io->isDebug()) {
      $this->logger->enforceDebug();
    }
    $this->promoteUser();

    $action = ActionBase::instance($this->actionKey);
    if (!$action || !($action instanceof ActionBaseInterface)) {
      $io->error('No valid action!');
      return FALSE;
    }

    $this->setActionArguments($action, $input);
    return $action;
  }

  /**
   * Set all arguments from the command line and pass them on to the action.
   *
   * @param \Drupal\drd\Plugin\Action\BaseInterface $action
   *   Action which will be executed.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Source of the arguments.
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
  }

}
