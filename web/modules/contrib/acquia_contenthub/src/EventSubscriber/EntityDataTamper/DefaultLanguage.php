<?php

namespace Drupal\acquia_contenthub\EventSubscriber\EntityDataTamper;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityDataTamperEvent;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Serialization\Yaml;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replace data conflicts with the local default language.
 *
 * CDF exports include languages. When these language ids conflict with the
 * installed local default language, we must opt to use that language instead.
 * This code replaces the entity_data array with the local entity without
 * changing the uuid key in the CDF. This allows imports to seamlessly
 * reference the local language as though it were the remote language.
 */
class DefaultLanguage implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_DATA_TAMPER][] = 'onDataTamper';
    return $events;
  }

  /**
   * Tamper with CDF data before its imported.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityDataTamperEvent $event
   *   The data tamper event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onDataTamper(EntityDataTamperEvent $event) {
    foreach ($event->getCdf()->getEntities() as $uuid => $object) {
      if ($object->getType() !== 'configurable_language') {
        continue;
      }
      foreach ($object->getAttribute('data')->getValue() as $langcode => $values) {
        $values = Yaml::decode($values);
        $entity = $this->loadEntityByUuid('configurable_language', $object->getUuid());
        if ($entity) {
          continue;
        }
        $entity = \Drupal::entityTypeManager()->getStorage('configurable_language')->create($values);
        /** @var \Drupal\language\ConfigurableLanguageInterface $old_entity */
        $old_entity = \Drupal::entityTypeManager()->getStorage('configurable_language')->load($entity->id());
        if (!$old_entity || !$old_entity->isDefault()) {
          continue;
        }
        $wrapper = new DependentEntityWrapper($old_entity, $object->getUuid());
        $event->getStack()->addDependency($wrapper);
        // There's only going to be one default language on a site, so we
        // can safely stop tampering with the data.
        return;
      }
    }
  }

  /**
   * A local copy of \Drupal\Core\Entity\EntityRepository::loadEntityByUuid.
   *
   * This copy exists due to certain caching issues with the entityTypeManager.
   * In order to side-step these issues, proper dependency injection has been
   * avoided. This forces us to go directly to the entity type manager on the
   * container through the static Drupal class. This will slightly complicate
   * testing, but ensures our code functions as desired.
   */
  protected function loadEntityByUuid($entity_type_id, $uuid) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    if (!$uuid_key = $entity_type->getKey('uuid')) {
      throw new EntityStorageException("Entity type $entity_type_id does not support UUIDs.");
    }

    $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadByProperties([$uuid_key => $uuid]);

    return $entities ? reset($entities) : NULL;
  }

}
