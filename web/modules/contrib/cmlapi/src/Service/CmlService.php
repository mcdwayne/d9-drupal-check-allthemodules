<?php

namespace Drupal\cmlapi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityManager;

/**
 * Class CmlService.
 */
class CmlService implements CmlServiceInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The EntityManager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Creates a new CmlService manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity Manager service.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      EntityManager $entity_manager
  ) {
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * Actual.
   */
  public function actual() {
    // Has 'progress' status.
    if ($current = $this->current()) {
      return $current;
    }
    // Has 'new' status, oldest.
    if ($next = $this->next()) {
      return $next;
    }
    // [failure|success] latest.
    if ($last = $this->last()) {
      return $last;
    }
    return FALSE;
  }

  /**
   * List.
   */
  public function all() {
    $list = $this->query();
    return $list;
  }

  /**
   * Next.
   */
  public function next() {
    $next = FALSE;
    $count = 1;
    $status = ['new'];
    if (!empty($list = $this->query($count, $status))) {
      $next = array_shift($list);
    }
    return $next;
  }

  /**
   * Last.
   */
  public function last() {
    $last = FALSE;
    $count = 1;
    $status = ['failure', 'success'];
    if (!empty($list = $this->query($count, $status, 'DESC'))) {
      $last = array_shift($list);
    }
    return $last;
  }

  /**
   * Current.
   */
  public function current() {
    $config = $this->configFactory->get('cmlapi.settings');
    $current = $config->get('runing_cml');
    if (!empty($current)) {
      $storage = $this->entityManager->getStorage('cml');
      return $storage->load($current);
    }
    $current = FALSE;
    $count = 1;
    $status = ['progress'];
    if (!empty($list = $this->query($count, $status))) {
      $current = array_shift($list);
    }
    return $current;
  }

  /**
   * Query.
   */
  public function query($count = FALSE, $status = ['new', 'progress'], $sort = 'ASC') {
    $entities = [];
    $entity_type = 'cml';
    $storage = $this->entityManager->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('status', 1)
      ->sort('created', $sort)
      ->condition('type', 'catalog')
      ->condition('state', $status, 'IN')
      ->condition('field_file', 'NULL', '!=');
    if ($count) {
      $query->range(0, $count);
    }
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Load.
   */
  public function load($id) {
    return $this->entityManager->getStorage('cml')->load($id);
  }

  /**
   * File Path.
   */
  public function getFilePath($cid, $xmlkey) {
    $filepath = FALSE;
    if (!$cid && $cml = $this->actual()) {
      $cid = $cml->id();
    }
    if (is_numeric($cid)) {
      $filepath = &drupal_static("CmlService::getFilePath():$xmlkey:$cid");
      if (!isset($filepath)) {
        $cache_key = "CmlService-$xmlkey:$cid";
        if ($cache = \Drupal::cache()->get($cache_key)) {
          $filepath = $cache->data;
        }
        else {
          $cml = $this->load($cid);
          if (is_object($cml)) {
            $cml_xml = $cml->field_file->getValue();
            $files = [];
            $data = FALSE;
            $filekeys[$xmlkey] = TRUE;
            if (!empty($cml_xml)) {
              foreach ($cml_xml as $xml) {
                $file = File::load($xml['target_id']);
                $filename = $file->getFilename();
                $pos = strpos($filename, 'import');
                if ($pos === 0) {
                  $filekey = 'import';
                }
                else {
                  $filekey = 'offers';
                }
                if (isset($filekeys[$filekey]) && $filekeys[$filekey]) {
                  $files[] = $file->getFileUri();
                }
              }
            }
            $filepath = array_shift($files);
            \Drupal::cache()->set($cache_key, $filepath);
          }
        }
      }
    }
    return $filepath;
  }

}
