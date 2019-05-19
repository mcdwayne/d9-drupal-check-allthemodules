<?php

namespace Drupal\user_agent_class;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CheckAgentServices.
 */
class CheckAgentServices implements CheckAgentServicesInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity device storage ID.
   */
  const DEVICE_ENTITY = 'device_entity';

  /**
   * Entity user agent storage ID.
   */
  const USER_AGENT_ENTITY = 'user_agent_entity';

  /**
   * Constructs a new CheckAgentServices object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Return classes.
   *
   * @param string $userAgent
   *   User Agent.
   *
   * @return string
   *   Line with classes names.
   */
  public function checkUserAgent($userAgent) {
    $deviceEntities = $this->entityTypeManager->getStorage(self::DEVICE_ENTITY)->loadByProperties([
      'enableCheck' => TRUE,
    ]);
    $browserEntities = $this->entityTypeManager->getStorage(self::USER_AGENT_ENTITY)->loadByProperties([
      'enableCheck' => TRUE,
    ]);
    $checkArray = [];
    $checkArray[] = $this->getClassNameFromList($deviceEntities, $userAgent);
    $checkArray[] = $this->getClassNameFromList($browserEntities, $userAgent);
    $classes = implode(" ", array_filter($checkArray));
    return $classes;
  }

  /**
   * Return List of browsers and devices.
   *
   * @return array
   *   List devices and browsers.
   */
  public function getListBrowsersDevices() {
    $deviceEntities = $this->entityTypeManager->getStorage(self::DEVICE_ENTITY)->loadByProperties([
      'enableCheck' => TRUE,
    ]);
    $browserEntities = $this->entityTypeManager->getStorage(self::USER_AGENT_ENTITY)->loadByProperties([
      'enableCheck' => TRUE,
    ]);
    $listDevicesAndBrowsers = [];
    $listDevicesAndBrowsers[] = $this->createSeparateListHelper(self::DEVICE_ENTITY, $deviceEntities);
    $listDevicesAndBrowsers[] = $this->createSeparateListHelper(self::USER_AGENT_ENTITY, $browserEntities);
    return $listDevicesAndBrowsers;
  }

  /**
   * Return String ClassName.
   *
   * @return string
   *   Class name.
   */
  public function getClassNameFromList($entities, $userAgent) {
    foreach ($entities as $entity) {
      if (!empty($entity->getExclude()) && preg_match('/\b' . $entity->getExclude() . '\b/', $userAgent) && preg_match('/\b' . $entity->label() . '\b/', $userAgent)) {
        continue;
      }
      elseif (preg_match('/\b' . $entity->label() . '\b/', $userAgent)) {
        $className = $entity->getClassName();
        return $className;
      }
    }
  }

  /**
   * Return String ClassName.
   *
   * @return array
   *   Helper method return array for devices and browsers.
   */
  public function createSeparateListHelper($entityStorage, $arrayEntities) {
    $result = [];
    foreach ($arrayEntities as $key => $arrayEntity) {
      $result[$entityStorage][$key]['trigger'] = $arrayEntity->label();
      $result[$entityStorage][$key]['exclude'] = $arrayEntity->getExclude();
      $result[$entityStorage][$key]['className'] = $arrayEntity->getClassName();
    }
    return $result;
  }

}
