<?php

namespace Drupal\staged_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\staged_content\DataProxy\DataProxyInterface;
use Drupal\staged_content\Storage\StorageHandlerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 *
 * @todo throw useful exceptions
 */
class LayeredImporter {

  /**
   * List of all the redefined id's.
   *
   * In case of duplication of id's a new id will be assigned.
   * If the id of an item was already set we'll reevaluate to preven errors
   * later. Note that all the references in the content storage are based on
   * the uuid, so this should not pose any issues. Except maybe in the rare
   * case of the 403, 404 and front page. So we'll emit a warning later for the
   * user to check the config manually.
   *
   * @var array
   *   List of all the id's that have been redefined.
   */
  protected $redefinedIds = [];

  /**
   * Defines relation domain URI for entity links.
   *
   * @var string
   */
  protected $linkDomain;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A list of vertex objects keyed by their link.
   *
   * @var array
   */
  protected $vertexes = [];

  /**
   * The graph entries.
   *
   * @var array
   */
  protected $graph = [];

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The storage handler with all the content.
   *
   * @var \Drupal\staged_content\Storage\StorageHandlerInterface
   *   The storage handler with all the content.
   */
  protected $storageHandler;

  /**
   * The output handler for the run.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   *   Output handler.
   */
  protected $output;

  /**
   * Constructs the default content manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   */
  public function __construct(Serializer $serializer, EntityTypeManagerInterface $entity_type_manager, AccountSwitcherInterface $account_switcher) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * Import all the data based on a given set of files.
   *
   * @param \Drupal\staged_content\Storage\StorageHandlerInterface $storageHandler
   *   The handler that holds all the information about the stored files.
   */
  public function importContent(StorageHandlerInterface $storageHandler) {
    $this->storageHandler = $storageHandler;
    $dataItems = $storageHandler->listDataItems();

    // Start by cleaning up the list from the straight keyed type to a
    // more structured one keyed by EntityType.
    $dataItems = $this->keyDataByEntityType($dataItems);

    // @TODO Improve output.
    foreach ($dataItems as $detectedTypeId => $info) {
      $this->getOutput()->writeln(sprintf("%s - count: %s", $detectedTypeId, count($info)));
    }

    // Load in all the items that should saved first.
    // E.g the items that should have their id's preserved.
    $this->precreateEntitiesWithPreservedId($dataItems);

    // Add the actual entities.
    $this->updateReferences($dataItems);

    // Print out the final report if needed.
    // @TODO Improve logging.
    if (!empty($this->redefinedIds)) {
      $this->getOutput()->writeln(" ======================== WARNING ======================== ");
      $this->getOutput()->writeln(" Duplicate id's were detected ");
      $this->getOutput()->writeln(" following content has been assigned a new id: ");

      foreach ($this->redefinedIds as $uuid => $entityInfo) {
        $this->getOutput()->writeln(sprintf("%s: %s", $uuid, $entityInfo));
      }
      $this->getOutput()->writeln(" ======================== ======= ======================== ");
    }
  }

  /**
   * Ensures all the items that have their id's preserved are added.
   *
   * This ensures both their id and their revision id are kept in tune.
   *
   * @param array $types
   *   Array of all the types that will be imported.
   */
  public function precreateEntitiesWithPreservedId(array $types) {
    // When preserving the keys an entity will be added a first time without
    // any of it's references (to prevent interference when generating
    // the correct id's).
    $context['ignore_references'] = TRUE;

    foreach ($types as $entityTypeId => $info) {

      if (count($info) == 0) {
        continue;
      }
      $this->getOutput()->writeLn('');
      $this->getOutput()->writeLn(sprintf('Handling %s: ', $entityTypeId));

      $dataList = $this->prepareList($entityTypeId, $info);

      if (count($dataList['preserved']) > 0) {
        // Import the "preserved" items first, and the "new" items second.
        $this->getOutput()->write('Preserved:');
        // @var \Drupal\staged_content\DataProxy\DataProxyInterface $dataProxy
        foreach ($dataList['preserved'] as $preservedId => $dataProxy) {
          // @TOOD Improve output.
          if ($this->getOutput()->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL) {
            $this->getOutput()->write('.');
          }
          $this->getOutput()->writeln(
            sprintf(" Precreating preserved item: %s: %s => %s",
              $dataProxy->getEntityType(),
              $preservedId,
              $dataProxy->getUuid()
            ),
            OutputInterface::VERBOSITY_VERBOSE
          );
          $this->importEntity($dataProxy, $context);
        }
        $this->getOutput()->writeLn('');
      }

      if (count($dataList['shifted']) > 0) {
        $this->getOutput()->write('Shifted: ');
        // Import the "new" items second since their id is not set in stone.
        foreach ($dataList['shifted'] as $dataProxy) {
          // @TOOD Improve output.
          if ($this->getOutput()
            ->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL
          ) {
            $this->getOutput()->write('.');
          }
          $this->getOutput()->writeln(
            sprintf("Precreating shifted item: %s => %s",
              $dataProxy->getEntityType(),
              $dataProxy->getUuid()
            ),
            OutputInterface::VERBOSITY_VERBOSE
          );
          $this->importEntity($dataProxy, $context, TRUE);
        }
        $this->getOutput()->writeLn('');
      }

      if (count($dataList['new']) > 0) {
        $this->getOutput()->write('New:');
        // Import the "new" items second since their id is not set in stone.
        foreach ($dataList['new'] as $dataProxy) {
          // @TOOD Improve output.
          if ($this->getOutput()
            ->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL
          ) {
            $this->getOutput()->write('.');
          }
          $this->getOutput()->writeln(
            sprintf("Precreating new item: %s => %s",
              $dataProxy->getEntityType(),
              $dataProxy->getUuid()
            ),
            OutputInterface::VERBOSITY_VERBOSE
          );
          $this->importEntity($dataProxy, $context);
        }
        $this->getOutput()->writeLn('');
      }
    }
  }

  /**
   * Add all the entities, since all the needed straight items have been added.
   *
   * @param array $types
   *   Array of all the types that will be imported.
   */
  public function updateReferences(array $types) {
    // Second pass, with auto importing of the references.
    $this->getOutput()->writeLn('');
    $this->getOutput()->writeLn('Completing all reference fields:');

    foreach ($types as $entityTypeId => $dataProxies) {
      /** @var \Drupal\staged_content\DataProxy\DataProxyInterface $dataProxy */
      foreach ($dataProxies as $dataProxy) {
        // @TODO Improve output.
        if ($this->getOutput()->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL) {
          $this->getOutput()->write('.');
        }
        $this->getOutput()->writeln(
          sprintf("Completing references for %s:%s",
            $dataProxy->getEntityType(),
            $dataProxy->getUuid()
          ),
          OutputInterface::VERBOSITY_VERBOSE
        );
        $this->importEntity($dataProxy);
      }
    }
    $this->getOutput()->writeLn('');
  }

  /**
   * Prepare/sort the list of items.
   *
   * This will ensure that any items that should preserve their id's are added
   * first and in the correct order. Afterwards any items that should not
   * preserve their id's are added.
   *
   * @param string $entityType
   *   The type of the entity.
   * @param \Drupal\staged_content\DataProxy\DataProxyInterface[] $info
   *   All the extra info connected to the entity type.
   *
   * @return array
   *   All the data to import, in the correct order.
   */
  public function prepareList(string $entityType, array $info) {
    $itemList = [
      'preserved' => [],
      'shifted' => [],
      'new' => [],
    ];

    foreach ($info as $uuid => $dataProxy) {
      // @TODO Probably cleaner to use the serializer here for the decoding.
      // But this is faster and easier to test for now.
      $data = $dataProxy->getData();

      if ($data['meta']['preserve_original_id']) {
        // If the id of an item was already set we'll reevaluate to prevent
        // errors later. Note that all the references in the content storage
        // are based on the uuid, so this should not pose any issues. Except
        // maybe in the rare case of the 403, 404 and front page.
        // So we'll emit a warning later for the user to check the config
        // manually.
        if (isset($itemList['preserved'][$data['meta']['original_id']])) {
          $this->getOutput()->writeln(sprintf("Preserved id altered!"));
          $this->getOutput()->writeln(sprintf("%s with id: %s was already defined for uuid: %s", $entityType, $data['meta']['original_id'], $itemList['preserved'][$data['meta']['original_id']]->getUuid()));

          // Place this item to the "new" queue to enforce generating a new id.
          $itemList['shifted'][] = $dataProxy;

          // Mark this item, so we can display the information later.
          $this->redefinedIds[$uuid] = $entityType . ':' . $data['meta']['original_id'] . ' --> ';
        }
        else {
          $itemList['preserved'][$data['meta']['original_id']] = $dataProxy;
        }
      }
      else {
        $itemList['new'][] = $dataProxy;
      }
    }

    ksort($itemList['preserved']);

    return $itemList;
  }

  /**
   * Key all the data items by their entity type.
   *
   * @param \Drupal\staged_content\DataProxy\DataProxyInterface[] $dataItems
   *   The data items in the set.
   *
   * @return array
   *   All the data items structured by entity type.
   */
  public function keyDataByEntityType(array $dataItems) {
    $return = [];

    foreach ($dataItems as $dataItem) {
      $return[$dataItem->getEntityType()][$dataItem->getUuid()] = $dataItem;
    }

    return $return;
  }

  /**
   * Gets the output interface.
   *
   * @return \Symfony\Component\Console\Output\OutputInterface
   *   Get the output interface.
   */
  public function getOutput() {

    if (!isset($this->output)) {
      $this->output = new ConsoleOutput();
    }

    return $this->output;
  }

  /**
   * Set the output interface.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output interface to set.
   */
  public function setOutput(OutputInterface $output) {
    $this->output = $output;
  }

  /**
   * Import a single entity.
   *
   * @param \Drupal\staged_content\DataProxy\DataProxyInterface $dataProxy
   *   The data proxy containing all the data.
   * @param array $context
   *   Extra context for the import/.
   * @param bool $stripPreservedId
   *   In some cases the preserved id has to be stripped from the data.
   *   This is most common in case of duplicate entity id's. Where the preserved
   *   Id is automatically shifted.
   */
  protected function importEntity(DataProxyInterface $dataProxy, array $context = [], bool $stripPreservedId = FALSE) {
    $context += [
      'ignore_references' => FALSE,
    ];

    $decoded = $this->serializer->decode($dataProxy->getRawData(), 'storage_json');

    // Hard strip the original id if relevant.
    if ($stripPreservedId) {
      $decoded['meta']['preserve_original_id'] = FALSE;
      unset($decoded['meta']['original_id']);
    }

    $class = $this->entityTypeManager->getDefinition($dataProxy->getEntityType())->getClass();
    $denormalized = $this->serializer->denormalize($decoded, $class, 'storage_json', $context);
    $denormalized->save();

    // If this item was marked as one of the items with it's id redefined,
    // Mark the new id here.
    if ($stripPreservedId && isset($this->redefinedIds[$denormalized->uuid()])) {
      $this->redefinedIds[$denormalized->uuid()] .= $denormalized->id();
    }
  }

}
