<?php

namespace Drupal\features_config_import\Commands;

use Drupal\features\Entity\FeaturesBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is a literal copy of the example Symfony Console command
 * from the documentation.
 *
 * See: http://symfony.com/doc/2.7/components/console/introduction.html#creating-a-basic-command
 */
class FeaturesConfigImportCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('features-config-import:run')
      ->setDescription('Run features configuration import')
      ->addOption(
        'bundle',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'If set, the task will only use a specific bundle'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $bundleEntity = NULL;
    // Disable interactive.
    $input->setInteractive(FALSE);

    $exportCommand = $this->getApplication()->find('config:export');

    $arguments = array(
      'command' => 'config:export',
      'label' => 'sync',
    );

    $configExportInput = new ArrayInput($arguments);
    $status = $exportCommand->run($configExportInput, $output);
    if ($status != 0) {
      return $status;
    }

    if (!empty($input->getOption('bundle'))) {
      $bundleEntity = FeaturesBundle::load($input->getOption('bundle'));
    }

    /* @var \Drupal\features\FeaturesManager $featureManager */
    $featureManager = \Drupal::service('features.manager');

    $modules = $featureManager->getFeaturesModules($bundleEntity);

    $destination_dir = \config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    $ignore = [
      'webform_demo_application_evaluation',
      'webform_demo',
      'webform_demo_event_registration',
      'webform_scheduled_email_test',
    ];

    foreach ($modules as $module) {
      if (in_array($module->getName(), $ignore)) {
        continue;
      }

      $files = file_scan_directory($module->getPath() . '/config/install', '/.*\.yml$/');
      foreach ($files as $file) {
        file_unmanaged_copy($file->uri, $destination_dir, FILE_EXISTS_REPLACE);
      }
    }

    $importCommand = $this->getApplication()->find('config:import');

    $arguments = array(
      'command' => 'config:import',
      'label' => 'sync',
    );

    $configImportInput = new ArrayInput($arguments);
    return $importCommand->run($configImportInput, $output);
  }

}
