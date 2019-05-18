<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class Download.
 *
 * @package Drupal\drd
 */
class Download extends BaseDomain {

  /**
   * Construct the Download command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_download';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:download')
      ->setDescription($this->trans('commands.drd.action.download.description'))
      ->addArgument(
        'source',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.download.arguments.source')
      )
      ->addArgument(
        'destination',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.download.arguments.destination')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $action->setActionArgument('source', $input->getArgument('source'));
    $action->setActionArgument('destination', $input->getArgument('destination'));
  }

}
