<?php

namespace Drupal\cmlapi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Class CmlCleaner.
 */
class CmlCleaner implements CmlCleanerInterface {

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
   * Get ids.
   */
  public function view() {
    $empty = $this->queryEmpty();
    $expired = $this->queryExpired();
    return array_merge($empty, $expired);
  }

  /**
   * Clean.
   */
  public function clean() {
    $empty = $this->deleteEmpty();
    $expired = $this->deleteExpired();
    return array_merge($empty, $expired);
  }

  /**
   * Empty delete.
   */
  public function deleteEmpty() {
    $ids = $this->queryEmpty();
    if (!empty($ids)) {
      $storage = \Drupal::entityManager()->getStorage('cml');
      foreach ($storage->loadMultiple($ids) as $id => $cml) {
        $cml->delete(TRUE);
      }
    }
    return $ids;
  }

  /**
   * Expired delete.
   */
  public function deleteExpired() {
    $ids = $this->queryExpired();
    if (!empty($ids)) {
      $config = $this->configFactory->get('cmlapi.mapsettings');
      $force = $config->get('cleaner-force');
      $storage = \Drupal::entityManager()->getStorage('cml');
      foreach ($storage->loadMultiple($ids) as $id => $cml) {
        foreach ($cml->field_file as $key => $value) {
          $file = $value->entity;
          if (is_object($file)) {
            $file->delete(TRUE);
          }
        }
        if ($force) {
          $dir = $this->cmlDir($cml);
          file_unmanaged_delete_recursive($dir);
        }
        $cml->delete(TRUE);
      }
    }
    return $ids;
  }

  /**
   * Empty cml.
   */
  public function queryEmpty() {
    $query = \Drupal::entityQuery('cml');
    $query->notExists('field_file')
      ->sort('created', 'ASC')
      ->range(0, 25);
    $ids = $query->execute();
    $result = [];
    if (!empty($ids)) {
      foreach ($ids as $id) {
        $result[$id] = $id;
      }
    }
    return $result;
  }

  /**
   * Expired cml.
   */
  public function queryExpired() {
    $config = $this->configFactory->get('cmlapi.mapsettings');
    $skip = $config->get('cleaner-keep');
    $expired = $config->get('cleaner-expired');
    $query = \Drupal::entityQuery('cml');
    $query->condition('field_file', 'NULL', '!=')
      ->condition('state', 'success', '=')
      ->condition('created', strtotime($expired), '<')
      ->sort('created', 'DESC')
      ->range($skip, 25);
    $ids = $query->execute();
    $result = [];
    if (!empty($ids)) {
      foreach ($ids as $id) {
        $result[$id] = $id;
      }
    }
    return $result;
  }

  /**
   * Get cml_id dir.
   */
  public function cmlDir($cml) {
    $config = $this->configFactory->get('cmlexchange.settings');
    $dir = 'cml-files';
    if ($config->get('file-path')) {
      $dir = $config->get('file-path');
    }
    $type = $cml->type->value;
    $time = format_date($cml->created->value, 'custom', 'Y-m-d--H-i-s');
    $key = substr($cml->uuid->value, 0, 8);
    $cid = $cml->id();
    $dir = "public://{$dir}/{$type}/{$time}-$key-{$cid}";
    return $dir;
  }

}
