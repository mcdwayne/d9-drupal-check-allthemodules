<?php

namespace Drupal\migrate_cron\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Migrate Cron settings for this site.
 */
class MigrateCronAdminSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migratePluginManager;

  /**
   * Constructs a new MigrateCronAdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MigrationPluginManagerInterface $plugin_manager) {
    parent::__construct($config_factory);
    $this->migratePluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_cron_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['migrate_cron.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_cron.settings');
    $migrations = $this->migratePluginManager->getDefinitions();
    if ($migrations) {
      foreach ($migrations as $migration) {

        $migrationId = $migration['id'];

        $form[$migrationId] = [
          '#type' => 'details',
          '#title' => $migration['label'],
          '#description' => $this->t("Migration cron settings for <em>@migration_id</em>", ['@migration_id' => $migrationId]),
          '#open' => TRUE,
        ];
        $form[$migrationId]["{$migrationId}_cron"] = [
          '#description' => $this->t('If checked, migration will run at cron.'),
          '#title' => $this->t('Run at cron'),
          '#type' => 'checkbox',
          '#default_value' => $config->get("{$migrationId}_cron"),
        ];

        $attributes = [
          'data-type' => 'number',
        ];
        if ($config->get("{$migrationId}_cron") == FALSE) {
          $attributes['disabled'] = TRUE;
        }

        $form[$migrationId]["{$migrationId}_interval"] = [
          '#maxlength' => 20,
          '#size' => 10,
          '#title' => 'Run at interval',
          '#description' => $this->t("The interval (in seconds) the migration should run.<br/>If left empty or the value is lower then the cron interval - migration will run at each cron."),
          '#type' => 'number',
          '#default_value' => ($config->get("{$migrationId}_cron") == FALSE ? NULL : $config->get("{$migrationId}_interval")),
          '#attributes' => $attributes,
          '#states' => [
            'disabled' => [
              ':input[name="' . $migrationId . '_cron"]' => ['checked' => FALSE],
            ],
          ],
        ];
      }
    }
    else {
      $form['migrations']['empty'] = [
        '#markup' => $this->t('There are no migrations.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('migrate_cron.settings');
    $migrations = $this->migratePluginManager->getDefinitions();
    if ($migrations) {
      foreach ($migrations as $migration) {
        $migrationId = $migration['id'];
        $config
          ->set("{$migrationId}_cron", $form_state->getValue("{$migrationId}_cron"))
          ->set("{$migrationId}_interval", $form_state->getValue("{$migrationId}_interval"))
          ->save();
      }
    }
  }

}
