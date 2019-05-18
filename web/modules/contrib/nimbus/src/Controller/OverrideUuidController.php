<?php

namespace Drupal\nimbus\Controller;

use Drupal\nimbus\UuidUpdaterInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class OverrideUuidController.
 *
 * @package Drupal\nimbus\Controller
 */
class OverrideUuidController {

  /**
   * The uuid updater.
   *
   * @var \Drupal\nimbus\UuidUpdaterInterface
   */
  private $uuidUpdater;

  /**
   * OverrideUuidController constructor.
   *
   * @param \Drupal\nimbus\UuidUpdaterInterface $uuid_updater
   *   The uuid updater.
   */
  public function __construct(UuidUpdaterInterface $uuid_updater) {
    $this->uuidUpdater = $uuid_updater;
  }

  /**
   * A command to update uuids.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The Symfony console input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The Symfony console output.
   *
   * @return bool
   *   Return false if something went wrong otherwise no return value.
   */
  public function uuidUpdateCommand(InputInterface $input, OutputInterface $output) {
    $result = $this->uuidUpdater->getEntries();
    $result = $this->uuidUpdater->filterEntries($result);
    if (!empty($result)) {
      $elements = [];
      foreach ($result as $config_name => $change_object) {
        $elements[] = [
          $config_name,
          $change_object->getActiveUuid(),
          $change_object->getStagingUuid(),
          $change_object->getStagingUuid(),
        ];
      }
      $table = new Table($output);
      $table
        ->setHeaders(['Config', 'Active', 'Staging', 'New'])
        ->setRows($elements);
      $table->render();
      $helper = new QuestionHelper();
      $question = new ConfirmationQuestion('You will reset the whole config, sure ?', !$input->isInteractive());

      if (!$helper->ask($input, $output, $question)) {
        $output->writeln('you canceled the override.');
        return FALSE;
      }

      foreach ($result as $key => $element) {
        $current_database_value = $element->getActiveConfig();
        $current_database_value['uuid'] = $element->getStagingUuid();
        $this->uuidUpdater->updateEntry($key, $current_database_value);
      }
      $output->writeln('Finished !');
    }
    else {
      $output->writeln('No wrong entries found.');
    }
  }

}
