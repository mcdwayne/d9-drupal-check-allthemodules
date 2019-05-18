<?php

namespace Drupal\cmlmigrations\Hook;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Hook Cron.
 */
class FormMigrationEditFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, &$form_state, $form_id) {
    $name = $form['id']['#default_value'];
    $config = \Drupal::config("migrate_plus.migration.{$name}");
    $source_plugins = self::getMigrationPlugins();
    $form['migrations-edit'] = [
      '#type' => 'details',
      '#title' => t('Migration Edit'),
      '#open' => TRUE,
      'migrations-source' => [
        '#type' => 'select',
        '#title' => 'Source Plugin',
        '#options' => $source_plugins,
        '#default_value' => $config->get('source')['plugin'],
      ],
      'migrations-process' => [
        '#title' => 'process',
        '#type' => 'textarea',
        '#attributes' => ['data-yaml-editor' => 'true'],
        '#default_value' => Yaml::dump($config->get('process')),
      ],
    ];
    array_unshift($form['#validate'], "Drupal\migration\Hook\FormMigrationEditFormAlter::submitConfig");
  }

  /**
   * Migration Plugins.
   */
  protected static function getMigrationPlugins() {
    $manager = FALSE;
    $plugins = [];
    try {
      $manager = \Drupal::service('plugin.manager.migrate.source');
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if ($manager) {
      foreach ($manager->getDefinitions() as $key => $source) {
        $plugins[$key] = "$key ({$source['provider'][0]})";
      }
    }
    return $plugins;
  }

  /**
   * Submit.
   */
  public static function submitConfig(array &$form, $form_state) {
    $source = ['plugin' => $form_state->getValue('migrations-source')];
    $process = Yaml::parse($form_state->getValue('migrations-process'));
    $name = $form['id']['#default_value'];
    $config = \Drupal::service('config.factory')->getEditable("migrate_plus.migration.{$name}");
    $config
      ->set('source', $source)
      ->set('process', $process)
      ->save();
    $form_state->setErrorByName('label', t('Error Message'));
    return FALSE;
  }

}
