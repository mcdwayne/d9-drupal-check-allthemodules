<?php

namespace Drupal\cmlmigrations\Service;

use Drupal\cmlapi\Service\CmlServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * InitMigrations.
 */
class PipeLine {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new Pileline manager.
   *
   * @param \Drupal\cmlmigrations\Service\MigrateServiceInterface $migrate_service
   *   The migrate service.
   * @param \Drupal\cmlmigrations\Service\ExecServiceInterface $exec_service
   *   The exec service.
   * @param \Drupal\cmlapi\Service\CmlServiceInterface $cml_service
   *   The cml service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity Manager service.
   */
  public function __construct(
      MigrateServiceInterface $migrate_service,
      ExecServiceInterface $exec_service,
      CmlServiceInterface $cml_service,
      ConfigFactoryInterface $config_factory,
      EntityManager $entity_manager) {
    $this->migrateService = $migrate_service;
    $this->execService = $exec_service;
    $this->cmlService = $cml_service;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * Init.
   */
  public function init($cml = FALSE) {
    $result = TRUE;
    // Берём актуальный обмен.
    if (!$cml) {
      $cml = $this->cmlService->actual();
    }
    $config = $this->configFactory->getEditable('cmlapi.settings');
    $status = $cml->getState();
    if (in_array($status, ['new', 'progress'])) {
      // Check previous exchanges $cml.
      $progress = $this->query('progress');
      if ($status == 'new' && !empty($progress)) {
        $cml = $progress;
      }
      $cid = $cml->id();
      $config->set('runing_cml', $cid)->save();
      // Opts: progress, success, failure.
      if ($migrations = $this->migrateService->getCmlGroup()) {
        $result = $this->import($cml, $migrations);
      }
    }
    $config->set('runing_cml', '')->save();
    return $result;
  }

  /**
   * Import.
   */
  private function import($cml, $migrations) {
    $id = $cml->id();
    $config = $this->configFactory->get('cmlmigrations.settings');
    // Таймаут default: 1 час.
    $timeout_min = $config->get('timeout');
    $timeout = $cml->changed->value + 60 * $timeout_min;
    $status = $cml->getState();
    \Drupal::logger(__CLASS__)->notice("go: $id = $status");
    // Первое обращение к обмену - меняем статус и запускаем.
    if ($status == 'new') {
      // Миграции готовы к запуску.
      if ($migrations['status']) {
        // Меняем статус.
        \Drupal::logger(__CLASS__)->notice("ok: $id = $status > progress (exec)");
        $cml->setState('progress');
        $cml->save();
        // Запускаем (Дата changed равна времени изменения).
        $this->execService->exec();
        sleep(5);
        return 'progress';
      }
      else {
        // Проверяем статус.
        if ($this->queryQuickRun($id, $config->get('timeout-quick-run'))) {
          $msg = "Too Quick, progress: $id = $status";
          \Drupal::logger(__CLASS__)->warning($msg);
          return 'progress';
        }
        else {
          // Если мы попали сюда - где-то ошибка.
          $msg = "fail: $id = $status > migrations busy";
          \Drupal::logger(__CLASS__)->error($msg);
          $cml->setState('busy');
          $cml->save();
          return 'failure';
        }

      }
    }
    // Уже импортируется.
    elseif ($status == 'progress') {
      // Миграция закончилась.
      if ($migrations['status']) {
        \Drupal::logger(__CLASS__)->notice("ok: $id = $status > success (done)");
        $cml->setState('success');
        $cml->save();
        return 'success';
      }
      // Миграция ещё не закончилась.
      if ($timeout > REQUEST_TIME) {
        // Пока ок, работаем.
        $time = format_date($timeout - REQUEST_TIME, 'custom', 'H:i:s');
        \Drupal::logger(__CLASS__)->notice("progress: $id / Time: $time");
        return 'progress';
      }
      else {
        // Время вышло.
        \Drupal::logger(__CLASS__)->error("fail: $id = $status > timeout");
        $cml->setState('failure');
        $cml->save();
        return 'failure';
      }
    }
    else {
      // Если мы попали сюда - где-то ошибка.
      \Drupal::logger(__CLASS__)->error("fail: $id = $status WTF?");
      $cml->setState('failure');
      $cml->save();
    }
    return 'failure';
  }

  /**
   * Query Quick Runner Check TRUE/FALSE.
   */
  public function queryQuickRun($id, $timeout) {
    if (!is_numeric($timeout)) {
      $timeout = 0;
    }
    $entities = [];
    $entity_type = 'cml';
    $storage = $this->entityManager->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('status', 1)
      ->condition('id', $id, '!=')
      ->condition('changed', REQUEST_TIME - $timeout, '>')
      ->condition('type', 'catalog')
      ->condition('state', ['success'], 'IN')
      ->sort('created', 'ASC');
    $ids = $query->execute();
    if (empty($ids)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Query.
   */
  public function query($status) {
    $entities = [];
    $entity_type = 'cml';
    $storage = $this->entityManager->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('status', 1)
      ->condition('state', [$status], 'IN')
      ->sort('created', 'ASC');
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return array_shift($entities);
  }

}
