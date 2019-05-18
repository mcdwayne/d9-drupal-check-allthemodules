<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DomainChange.
 *
 * @package Drupal\drd
 */
class DomainChange extends BaseDomain {

  /**
   * Construct the DomainChange command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_domainchange';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:domainchange')
      ->setDescription($this->trans('commands.drd.action.domainchange.description'))
      ->addOption(
        'newdomain',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.domainchange.arguments.newdomain')
      )
      ->addOption(
        'secure',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.domainchange.arguments.secure')
      )
      ->addOption(
        'port',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.domainchange.arguments.port')
      )
      ->addOption(
        'force',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.domainchange.arguments.force')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    if ($newdomain = $input->getOption('newdomain')) {
      $action->setActionArgument('newdomain', $newdomain);
    }
    if ($secure = $input->getOption('secure')) {
      $action->setActionArgument('secure', $secure);
    }
    if ($port = $input->getOption('port')) {
      $action->setActionArgument('port', $port);
    }
    if ($force = $input->getOption('force')) {
      $action->setActionArgument('force', TRUE);
    }
    else {
      $action->setActionArgument('force', FALSE);
    }
  }

}
