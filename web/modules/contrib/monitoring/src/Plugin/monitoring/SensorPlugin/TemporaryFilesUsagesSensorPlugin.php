<?php

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Monitors temporary files usages.
 *
 * @SensorPlugin(
 *   id = "temporary_files_usages",
 *   label = @Translation("Used temporary files"),
 *   description = @Translation("Reports temporary files that are still being used."),
 *   addable = FALSE
 * )
 */
class TemporaryFilesUsagesSensorPlugin extends DatabaseAggregatorSensorPlugin {

  /**
   * {@inheritdoc}
   */
  protected $configurableConditions = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $configurableTable = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $configurableVerboseOutput = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function getQuery() {
    $query = parent::getQuery();
    $query->innerJoin('file_usage', 'fu', 'fu.fid = file_managed.fid');
    $query->groupBy('file_managed.fid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAggregateQuery() {
    $query = parent::getAggregateQuery();
    $query->innerJoin('file_usage', 'fu', 'fu.fid = file_managed.fid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function addAggregateExpression(SelectInterface $select) {
    $select->addExpression('COUNT(DISTINCT file_managed.fid)', 'records_count');
  }

  /**
   * {@inheritdoc}
   */
  public function buildTableHeader($rows = []) {
    return [
      t('ID'),
      t('Filename'),
      t('Usages'),
      t('Status'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTableRows(array $results) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $rows = [];
    foreach ($results as $delta => $row) {
      $types = [];
      $fid = $row->fid;
      $file = File::load($fid);
      /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
      $file_usage = \Drupal::service('file.usage');

      // List usages for the file.
      foreach ($file_usage->listUsage($file) as $usages) {
        foreach ($usages as $type => $usage) {
          foreach ($usage as $id => $value) {
            // Check if the entity type has a definition for this type.
            if ($entity_type_manager->hasDefinition($type)) {
              $entity = $entity_type_manager->getStorage($type)->load($id);
              // Create the link.
              if ($entity && $entity->hasLinkTemplate('canonical')) {
                $types[] = $entity->toLink()->toRenderable();
              }
              else {
                $types[] = ['#markup' => t('Missing @type/@id', [
                  '@type' => $type,
                  '@id' => $id,
                ])];
              }
            }
            // If the type can not be loaded, display the type and id.
            else {
              $types[] = ['#markup' => $type . '/' . $id];
            }
            // Separate the files usages list with a comma.
            $types[] = ['#markup' => ', '];
          }
        }
      }

      // If there are usages, format the rows to be rendered.
      if (!empty($types)) {
        // Delete the last unnecessary comma.
        array_pop($types);
        $filename = Link::fromTextAndUrl($file->getFilename(), Url::fromUri(file_create_url($file->getFileUri())));
        $status = Link::createFromRoute('Make permanent', 'monitoring.make_file_permanent', [
          'monitoring_sensor_config' => $this->sensorConfig->id(),
          'file' => $fid
        ]);

        $rows[] = [
          'fid' => $fid,
          'filename' => $filename,
          'usages' => render($types),
          'status' => $status,
        ];
      }
    }

    return $rows;
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['aggregation']['#access'] = FALSE;
    return $form;
  }

}
