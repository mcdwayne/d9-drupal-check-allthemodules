<?php

namespace Drupal\env_dependencies\Commands;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\env_dependencies\EnvDependenciesEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is a literal copy of the example Symfony Console command
 * from the documentation.
 *
 * See: http://symfony.com/doc/2.7/components/console/introduction.html#creating-a-basic-command
 */
class EnvDependenciesCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('env-dependencies:run')
      ->setDescription('Run environment')
      ->addOption(
        'status_only',
        NULL,
        InputOption::VALUE_NONE,
        'If set, the task will only show the changing status'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Remove info cache.
    \Drupal::cache()->delete('system.module.info');
    $config = \Drupal::config('env_dependencies.settings');

    if (empty($environment)) {
      $environment = $config->get('environment');
    }
    if (!$input->getOption('status_only')) {
      $output->writeln('Switching to ' . $environment);
    }
    else {
      $output->writeln('Switching to status: ' . $environment);
    }

    // Environment.
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = new EnvDependenciesEvent($environment, $config);

    $modules = system_get_info('module');
    $default_dependencies = [];
    $enable_modules = [];

    if ($profile = drupal_get_profile()) {
      $default_dependencies[$profile] = TRUE;
      $list_profile = \Drupal::service('extension.list.profile');
      if (method_exists($list_profile, 'getAncestors')) {
        foreach ($list_profile->getAncestors($profile) as $key => $profile) {
          $default_dependencies[$key] = TRUE;
        }
      }
    }

    foreach ($modules as $module_name => $module) {
      if (!empty($module['dependencies']) || !empty($module['install'])) {
        foreach (array_merge($module['dependencies'] ?? [], $module['install'] ?? []) as $dependency) {
          $dependency = ModuleHandler::parseDependency($dependency);

          if (array_key_exists($dependency['name'], $modules)) {
            $default_dependencies[$dependency['name']] = TRUE;
          }
          else {
            $default_dependencies[$dependency['name']] = FALSE;
            $enable_modules[$dependency['name']] = TRUE;
          }
        }
      }

      if (!empty($module[$environment . '_dependencies'])) {
        foreach ($module[$environment . '_dependencies'] as $dependency) {
          $dependency = ModuleHandler::parseDependency($dependency);
          if (array_key_exists($dependency['name'], $modules)) {
            $default_dependencies[$dependency['name']] = TRUE;
          }
          else {
            $default_dependencies[$dependency['name']] = FALSE;
            $enable_modules[$dependency['name']] = TRUE;
          }
        };
      }
    }

    $moduleInstaller = \Drupal::service('module_installer');
    if (count($enable_modules)) {
      $output->writeln('Enabling modules: ' . implode(array_keys($enable_modules), ','));
      $dispatcher->dispatch(EnvDependenciesEvent::BEFORE_ENABLE_DEPENDENCIES, $event);
      if (!$input->getOption('status_only')) {
        $moduleInstaller->install(array_keys($enable_modules));
      }
    }

    $uninstall_modules = array_diff(array_keys($modules), array_keys($default_dependencies), array_keys($enable_modules));

    if (count($uninstall_modules)) {
      $output->writeln('Uninstall modules: ' . implode($uninstall_modules, ','));
      if (!$input->getOption('status_only')) {
        $moduleInstaller->uninstall($uninstall_modules, FALSE);
      }
    }

    if (!$input->getOption('status_only')) {
      $dispatcher->dispatch(EnvDependenciesEvent::AFTER_ENABLE_DEPENDENCIES, $event);
      $output->writeln('Switched to: ' . $environment);
    }
  }

}
