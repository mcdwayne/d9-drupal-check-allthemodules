<?php

namespace Drupal\bigcommerce\Form;

use BigCommerce\Api\v3\Api\CatalogApi;
use Drupal\bigcommerce\Batch\MigrateUpgradeImportBatch;
use Drupal\bigcommerce\Exception\UnconfiguredException;
use Drupal\bigcommerce\Plugin\migrate\source\BigCommerceSource;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to trigger a synchronisation of data from BigCommerce.
 */
class ProductSync extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Key-value store service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * @var \BigCommerce\Api\v3\Api\CatalogApi|null
   */
  protected $catalogApi = NULL;

  /**
   * ProductSync constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationPluginManager
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValue
   * @param \BigCommerce\Api\v3\Api\CatalogApi|null $catalogApi
   */
  public function __construct(MigrationPluginManagerInterface $migrationPluginManager, DateFormatterInterface $dateFormatter, KeyValueFactoryInterface $keyValue, CatalogApi $catalogApi = NULL) {
    $this->migrationPluginManager = $migrationPluginManager;
    $this->dateFormatter = $dateFormatter;
    $this->keyValue = $keyValue;
    $this->catalogApi = $catalogApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    try {
      $catalog_api = $container->get('bigcommerce.catalog');
    }
    catch (UnconfiguredException $e) {
      $catalog_api = NULL;
    }
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('date.formatter'),
      $container->get('keyvalue'),
      $catalog_api
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bigcommerce_product_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->catalogApi) {
      $this->messenger()->addError($this->t('BigCommerce must be configured before synchronising products.'));
      return $this->redirect('bigcommerce.settings');
    }

    $rows = [];
    $migrate_last_imported_store = $this->keyValue->get('migrate_last_imported');
    $total_changes = 0;
    foreach ($this->getMigrations() as $migration_id => $migration) {
      try {
        $map = $migration->getIdMap();
        $imported = $map->importedCount();
        $source_plugin = $migration->getSourcePlugin();
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t(
          'Failure retrieving information on @migration: @message',
          ['@migration' => $migration_id, '@message' => $e->getMessage()]
        ));
        continue;
      }

      try {
        $source_rows = $source_plugin->count();

        // -1 indicates uncountable sources. This should not occur for
        // BigCommerce.
        if ($source_rows == -1) {
          throw new \RuntimeException('Unexpected uncountable source');
        }
        else {
          // Determine if any updates need to be made to content entities.
          if ($source_plugin instanceof BigCommerceSource && $source_plugin->trackChanges()) {
            /** @var \Drupal\migrate\Row $row */
            foreach ($source_plugin as $row) {
              if ($row->changed()) {
                $map->setUpdate($row->getSourceIdValues());
              }
            }
          }
          $unprocessed = $source_rows - $map->processedCount();
          $toupdate = $map->updateCount();
          $total_changes += $unprocessed + $toupdate;
        }
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t(
          'Could not retrieve source count from @migration: @message',
          ['@migration' => $migration_id, '@message' => $e->getMessage()]
        ));
        continue;
      }

      $last_imported = $migrate_last_imported_store->get($migration->id(), FALSE);
      if ($last_imported) {
        $last_imported = $this->dateFormatter->format(
          $last_imported / 1000,
          'custom',
          'Y-m-d H:i:s'
        );
      }
      else {
        $last_imported = '';
      }
      $rows[] = [
        // @todo could use label but that is very long.
        'id' => $migration_id,
        'status' => $migration->getStatusLabel(),
        'total' => $source_rows,
        'imported' => $imported,
        'unprocessed' => $unprocessed,
        'toupdate' => $toupdate,
        'last_imported' => $last_imported,
      ];
    }

    $header = [
      '',
      $this->t('Status'),
      $this->t('Total'),
      $this->t('Imported'),
      $this->t('Unprocessed'),
      $this->t('To update'),
      $this->t('Last imported'),
    ];

    $form['sync_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Details'),
      '#weight' => 20,
    ];
    $form['sync_details']['status_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('There is nothing to sync. Set up BigCommerce.'),
    ];

    if ($total_changes > 0) {
      $message = $this->t('BigCommerce product updates available.');
      $class = 'messages--warning';
    }
    else {
      $message = $this->t('BigCommerce products up-to-date.');
      $class = 'messages--status';
    }

    $form['sync'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Status'),
      '#weight' => 0,
    ];
    $form['sync']['message'] = [
      '#markup' => $message,
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => ['messages', $class],
      ],
    ];

    $form['sync']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync products from BigCommerce'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migrations = array_map(function (Migration $migration) {
      return $migration->id();
    }, $this->getMigrations());
    $batch = [
      'title' => $this->t('Syncing products from BigCommerce'),
      'progress_message' => '',
      'operations' => [
        [
          [MigrateUpgradeImportBatch::class, 'run'],
          [$migrations],
        ],
      ],
      'finished' => [
        MigrateUpgradeImportBatch::class, 'finished',
      ],
    ];
    batch_set($batch);
  }

  /**
   * Gets all the migrations for BigCommerce syncing.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getMigrations() {
    $plugins = $this->migrationPluginManager->createInstances([]);
    return array_filter($plugins, function (Migration $migration) {
      return in_array('BigCommerce', $migration->getMigrationTags(), TRUE);
    });
  }

}
