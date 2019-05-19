<?php

namespace Drupal\widget_engine_domain_access;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\domain_access\DomainAccessManager;

/**
 * Checks the access status of entities based on domain settings.
 */
class WidgetEngineDomainAccessManager extends DomainAccessManager {

  /**
   * @inheritdoc
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $item = array();
    switch ($entity->getEntityType()->id()) {
      case 'user':
      case 'node':
      case 'widget':
        if ($entity->isNew()) {
          /** @var \Drupal\domain\DomainInterface $active */
          if ($active = \Drupal::service('domain.negotiator')->getActiveDomain()) {
            $item[0]['target_uuid'] = $active->uuid();
          }
        }
        // This code does not fire, but it should.
        else {
          foreach (self::getAccessValues($entity) as $id) {
            $item[] = $id;
          }
        }
        break;
      default:
        break;
    }
    return $item;
  }

}
