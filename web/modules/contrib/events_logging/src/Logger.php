<?php

namespace Drupal\events_logging;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Psr\Log\LoggerInterface as DrupalLogger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Logger.
 */
class Logger implements LoggerInterface {

  /**
   * @var \Drupal\events_logging\StorageBackendPluginManagerInterface
   */
  private $storageBackendPluginManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $drupalLogger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Logger constructor.
   *
   * @param \Drupal\events_logging\StorageBackendPluginManagerInterface $storage_backend_plugin_manager
   * @param \Psr\Log\LoggerInterface $drupal_logger
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   */
  public function __construct(
    StorageBackendPluginManagerInterface $storage_backend_plugin_manager,
    DrupalLogger $drupal_logger,
    ConfigFactoryInterface $config,
    EntityTypeManager $entity_type_manager,
    RequestStack $request
  ) {
    $this->storageBackendPluginManager = $storage_backend_plugin_manager;
    $this->drupalLogger = $drupal_logger;
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request;
  }

  /**
   * @param array $data
   */
  public function log($data) {
    // Uncomment this once the storage_backend_id is configurable.
    // $storageBackendId = $this->config->get('events_logging.settings')->get('storage_backend_id');
    
    try {
      /** @var $storageBackend \Drupal\events_logging\StorageBackendInterface */
      $storageBackend = $this->storageBackendPluginManager->createInstance('database');
      $storageBackend->save($data);
    } catch (PluginException $e) {
      $this->drupalLogger->error("Errors in logging data %data: %error", [
        print_r($data, TRUE),
        $e->getMessage(),
      ]);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public function checkIfEntityIsEnabled(EntityInterface $entity){
    $entity_type_id = $entity->getEntityType()->id();
    $events_logging_config = $this->config->get('events_logging.config');
    $events_logging_content_entities = $events_logging_config->get('enabled_content_entities') ? $events_logging_config->get('enabled_content_entities') : [];
    $events_logging_config_entities = $events_logging_config->get('enabled_config_entities') ? $events_logging_config->get('enabled_config_entities') : [];
    if($entity_type_id == 'events_logging'){
      return FALSE;
    }
    if(in_array($entity_type_id,$events_logging_content_entities) || in_array($entity_type_id,$events_logging_config_entities)){
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $type
   * @param $description
   */
  public function createLogEntity(EntityInterface $entity, $type, $description=NULL) {
    $values = [];
    $values['type'][0]['value'] = $entity->getEntityType()->id() . '_' . $type;
    $values['operation'][0]['value'] = $type;
    $values['logpath'][0]['value'] = $this->request->getCurrentRequest()->getRequestUri();
    $values['ref_numeric'][0]['value'] = $entity->id();
    $entity_arr = $entity->toArray();
    //manage title for standard nodes and name for custom content entities
    $title = isset($entity->toArray()['title']) ? $entity->toArray()['title'][0]['value'] : FALSE;
    if(!$title){
      $title = isset($entity->toArray()['name'][0]['value']) ? $entity->toArray()['name'][0]['value'] : '';
    }
    $values['name'][0]['value'] = $title;
    $values['description'][0]['value'] = $description ? $description : $this->getLogDescription($entity,$type);
    $values['ip'][0]['value'] = $this->request->getCurrentRequest()->getClientIp();
    if(isset($entity_arr['title'][0]['value'])){
      $title = $entity_arr['title'][0]['value'];
    } elseif(isset($entity_arr['name'][0]['value'])) {
      $title = $entity_arr['name'][0]['value'];
    }
    $values['ref_title'][0]['value'] = $title;
    $events_logging_storage = $this->entityTypeManager->getStorage('events_logging');
    $events_logging_entity = $events_logging_storage->create($values);

    try {
      $events_logging_entity->save();
    }
    catch (EntityStorageException $e) {
      $this->drupalLogger->error("Errors in saving entity %data: %error", [
        '%data' => print_r($values, TRUE),
        '%error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $type
   *
   */
  protected function getLogDescription(EntityInterface $entity, $type){
    $name = \Drupal::currentUser()->getAccountName();
    $uid = \Drupal::currentUser()->id();
    $entname = $entity->getEntityType()->getLabel();
    $entid = $entity->id();
    $description = "user $name (uid $uid) performed $type operation on entity $entname (id $entid)";
    return $description;
  }

  /**
   *
   */
  public function PurgeOldLogs(){
    $config = $this->config->get('events_logging.config');
    $max_records = $config->get('max_records');
    if ($max_records) {
      $events_logging_storage = $this->entityTypeManager->getStorage('events_logging');
      $query = $events_logging_storage->getQuery();
      $query->sort('created', 'DESC');
      $results = $query->execute();

      if (!empty($results)) {
        $delete_ids = array_slice($results, $max_records);
        $delete_records = $events_logging_storage->loadMultiple($delete_ids);
        try {
          $events_logging_storage->delete($delete_records);
        } catch (EntityStorageException $e) {
          $this->drupalLogger->error("Errors in deleting rows %rows: %error", [
            '%rows' => print_r($delete_records, TRUE),
            '%error' => $e->getMessage(),
          ]);
        }
      }
    }
  }

}
