<?php

namespace Drupal\drd\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Library.
 *
 * @package Drupal\drd
 */
class Library extends BaseDomain {

  /**
   * Construct the Library command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_library';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:library:push')
      ->setDescription($this->trans('commands.drd.action.library.push.description'))
      ->addArgument(
        'source',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.library.push.arguments.source')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    // Source argument.
    $source = $input->getArgument('source');
    if (!$source) {
      $source = $io->choice(
        $this->trans('commands.drd.action.library.push.questions.source'),
        ['official', 'local']
      );
      $input->setArgument('source', $source);
    }

    parent::interact($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $action->setActionArgument('source', $input->getArgument('source'));
  }

}
