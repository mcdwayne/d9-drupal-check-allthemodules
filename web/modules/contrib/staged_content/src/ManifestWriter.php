<?php

namespace Drupal\staged_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * A service to write out a manifest file based on the entity types provided.
 *
 * The manifest file contains all the data about the data that was exported.
 * Making it the main source of truth for all the "underlying" files.
 *
 * It's used to stabilize the data structure before writing it out completely.
 */
class ManifestWriter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The reference reader service.
   *
   * @var \Drupal\staged_content\ReferenceReader
   *   Reference reader service.
   */
  protected $referenceReader;

  /**
   * The marker detection manager.
   *
   * @var \Drupal\staged_content\MarkerDetectorManagerInterface
   *   The plugin detection helper for drupal.
   */
  protected $markerDetectorManager;

  /**
   * Holds an interface to extract the marker for an entity.
   *
   * @var \Drupal\staged_content\Plugin\StagedContent\Marker\MarkerDetectorInterface
   *   The method of detecting the marker for this item.
   */
  protected $markerDetector;

  /**
   * All the markers to detect in the various items to export.
   *
   * @var string[]
   *   All the markers to detect in labels.
   */
  protected $markers = [];

  /**
   * All the entity types to export.
   *
   * @var array
   *   All the entity types to export.
   */
  protected $entityTypes = [];

  /**
   * The actual file to output the data to.
   *
   * @var string
   *   The outputfile for this manifest.
   */
  protected $outputFile;

  /**
   * The data to output to the manifest.
   *
   * @var array
   *   Data for the manifest.
   */
  protected $manifestData = [];

  /**
   * Constructs the default content manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\staged_content\ReferenceReader $referenceReader
   *   The reference reader service.
   * @param \Drupal\staged_content\MarkerDetectorManagerInterface $markerDetectorManager
   *   Marker detection manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ReferenceReader $referenceReader, MarkerDetectorManagerInterface $markerDetectorManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->referenceReader = $referenceReader;
    $this->markerDetectorManager = $markerDetectorManager;
  }

  /**
   * Write out a manifest file with the data.
   *
   * @param string $outputFile
   *   The output file to write the manifest to.
   * @param array $config
   *   Write out a manifest file based on the config.
   */
  public function write(string $outputFile, array $config = []) {

    $this->outputFile = $outputFile;

    $this->bootstrapConfig($config);

    $this->handleEntityTypes();

    $fs = new Filesystem();
    $fs->dumpFile($outputFile, Yaml::dump($this->manifestData, 5, 2));
  }

  /**
   * Generate the data for all the basic entities to export.
   */
  protected function handleEntityTypes() {
    foreach ($this->entityTypes as $key => $info) {
      if (!isset($info['entityType'])) {
        echo sprintf('Skipping %s since it does not specify an "entityType" key', $key) . "\n";
        continue;
      }
      $entityTypeId = $info['entityType'];
      $query = $this->entityTypeManager->getStorage($entityTypeId)->getQuery();
      $entityIds = $query->execute();

      // @TODO, improve output logging.
      echo sprintf('Exporting %s %s items', count($entityIds), $entityTypeId) . "\n";

      foreach ($entityIds as $entityId) {
        $storage = $this->entityTypeManager->getStorage($entityTypeId);
        $entity = $storage->load($entityId);
        $marker = $this->markerDetector->detectMarker($entity, $this->markers);

        $this->manifestData[$marker][$entityTypeId][$entity->uuid()] = [
          'label' => $entity->label(),
        ];

        // Connect all the referenced items.
        $includedReferenceTypes = ['paragraph', 'media', 'file'];
        $referencedItems = $this->referenceReader->detectReferencesRecursively($entity, $includedReferenceTypes);
        // Exclude the actual parent entity.
        unset($referencedItems[$entity->uuid()]);
        $this->manifestData[$marker][$entityTypeId][$entity->uuid()]['attached'] = array_keys($referencedItems);

        foreach ($referencedItems as $item) {
          $referencedMarker = $this->markerDetector->detectMarker($item, $this->markers);
          $this->manifestData[$referencedMarker][$item->getEntityTypeId()][$item->uuid()] = [
            'label' => $item->label(),
          ];
        }
      }
    }
  }

  /**
   * Complete the config with defaults.
   *
   * @param array $config
   *   All the configuration passed to this item.
   */
  protected function bootstrapConfig(array $config) {
    if (!isset($config['markers'])) {
      $config['markers'] = ['prod', 'acc', 'test', 'dev'];
    }

    if (!isset($config['entity_types'])) {
      $config['entity_types'] = [
        'node' => [
          'entityType' => 'node',
        ],
      ];
    }

    $config['marker_detector_plugin'] = isset($config['marker_detector_plugin'])
      ? $config['marker_detector_plugin']
      : 'label';

    $this->markerDetector =
      $this->markerDetectorManager->createInstance($config['marker_detector_plugin']);

    $this->markers = $config['markers'];
    $this->entityTypes = $config['entity_types'];
  }

}
