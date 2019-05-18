<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extracts all term reference fields and collates terms into an attribute.
 */
class EntityTagsCdfAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    return $events;
  }

  /**
   * Method called on the POPULATE_CDF_ATTRIBUTES event.
   *
   * Extracts taxonomy terms associated with this entity across all languages
   * and populates them as top level CDF attributes.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   The CdfAttributesEvent object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ContentEntityInterface) {
      $cdf = $event->getCdf();
      $tags = [];
      // @var string $field_name
      // @var \Drupal\Core\Field\FieldItemListInterface $field
      foreach ($entity as $field_name => $field) {
        if ($field->getFieldDefinition()->getType() == 'entity_reference' && $field->getFieldDefinition()->getSettings()['target_type'] == 'taxonomy_term') {
          foreach ($field as $item) {
            if (!$item->entity) {
              $entity = \Drupal::entityTypeManager()->getStorage($field->getFieldDefinition()->getSetting('target_type'))->load($item->getValue()['target_id']);
              if (is_null($entity)) {
                continue;
              }
              $item->entity = $entity;
            }
            $tags[] = $item->entity->uuid();
          }
        }
      }
      if ($tags) {
        $cdf->addAttribute('tags', CDFAttribute::TYPE_ARRAY_REFERENCE, $tags);
      }
    }
  }

}
