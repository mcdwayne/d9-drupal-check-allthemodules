<?php

namespace Drupal\autotrader_csv;

use Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports one or more records for a node to a string or to a file.
 *
 * Note we're implementing ContainerFactoryPluginInterface because it "defines
 * an interface for pulling plugin dependencies from the container."
 */
class NodeExporter implements NodeExporterInterface, ContainerFactoryPluginInterface {

  /**
   * The node to export the records for.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * The filename that the records will be written to, when exporting to file.
   *
   * @var string
   */
  protected $filename;

  /**
   * The NodeExporter plugin manager.
   *
   * We use this to get all of the NodeExporter plugins.
   *
   * @var \Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportManager
   */
  protected $bundleExportManager;

  /**
   * Constructor.
   *
   * @param \Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportManager $bundle_export_manager
   *   The NodeExporter plugin manager service. We're injecting this service
   *   so that we can use it to access the NodeExporter plugins.
   */
  public function __construct(AutotraderCsvNodeExportManager $bundle_export_manager) {
    $this->bundleExportManager = $bundle_export_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportManager $bundle_export_manager */
    $bundle_export_manager = $container->get('plugin.manager.autotrader_csv_node_export');
    return new static($bundle_export_manager);
  }

  /**
   * Constructs an NodeExporter object.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The nodes to export the records for.
   * @param string $filename
   *   The filename to write the exported records to. NULL can be given when not
   *   exporting to a file.
   */
  public function setup(array $nodes, $filename = NULL) {
    $this->nodes = $nodes;
    $this->filename = $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function toString() {
    $lines = $this->generateRecords();
    return implode(PHP_EOL, $lines);
  }

  /**
   * {@inheritdoc}
   */
  public function toFile() {
    $lines = $this->generateRecords();
    $lines = implode(PHP_EOL, $lines);
    // @todo - Allow for private storage.
    return file_save_data($lines, 'public://' . $this->filename, FILE_EXISTS_REPLACE);
  }

  /**
   * Generates the records for the CSV file.
   *
   * @return string[]
   *   An array containing the string representation for the records.
   */
  protected function generateRecords() {
    $add_csv_col_row = TRUE;
    $records = [];

    foreach ($this->nodes as $node) {
      // The array of plugin definitions is keyed by plugin id, so we can just
      // use that to load our plugin instances.
      $plugin_definitions = $this->bundleExportManager->getDefinitions();
      $plugin_id = $node->bundle();
      if (in_array($plugin_id, array_keys($plugin_definitions))) {
        /** @var \Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportBase $plugin */
        try {
          $plugin = $this->bundleExportManager->createInstance($plugin_id);
          $plugin->setNode($node);
          if ($add_csv_col_row) {
            $records[] = $plugin->csvColToString();
            $add_csv_col_row = FALSE;
          }
          $records[] = $plugin->toString();
        }
        catch (\Exception $exception) {
          watchdog_exception("autotrader_csv", $exception);
        }
      }
    }

    return $records;
  }

}
