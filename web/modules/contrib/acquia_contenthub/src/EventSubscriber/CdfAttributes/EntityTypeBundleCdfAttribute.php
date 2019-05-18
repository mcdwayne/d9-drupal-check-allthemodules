<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extract basic entity information and creates attributes for them.
 *
 * This includes:
 *  - Entity Label.
 *  - Entity Type.
 *  - Bundle.
 */
class EntityTypeBundleCdfAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    return $events;
  }

  /**
   * Handles POPULATE_CDF_ATTRIBUTES event.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   Event.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $entity = $event->getEntity();
    $cdf = $event->getCdf();
    if ($cdf->getType() == 'drupal8_config_entity' || $entity instanceof ContentEntityInterface) {
      if (!$cdf->getAttribute('label')) {
        $cdf->addAttribute('label', CDFAttribute::TYPE_ARRAY_STRING);
        $attribute = $cdf->getAttribute('label');
        if ($entity instanceof TranslatableInterface) {
          foreach ($entity->getTranslationLanguages() as $language) {
            $translated_entity = $entity->getTranslation($language->getId());
            $attribute->setValue($translated_entity->label(), $language->getId());
          }
        }
        else {
          $attribute->setValue($entity->label(), $entity->language()->getId());
        }

        if (!array_key_exists(CDFObject::LANGUAGE_UNDETERMINED, $attribute->getValue())) {
          $attribute->setValue(implode(' ', $attribute->getValue()));
        }
      }
      $cdf->addAttribute('entity_type', CDFAttribute::TYPE_STRING, $entity->getEntityTypeId());
      $cdf->addAttribute('entity_type_label', CDFAttribute::TYPE_STRING, $entity->getEntityType()->getLabel());
    }

    if ($entity instanceof ContentEntityInterface) {
      $cdf->addAttribute('bundle', CDFAttribute::TYPE_STRING, $entity->bundle());
      /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
      $definition = \Drupal::entityTypeManager()->getDefinition($entity->getEntityTypeId());
      if ($definition->getBundleEntityType()) {
        $bundle_entity = \Drupal::entityTypeManager()
          ->getStorage($definition->getBundleEntityType())
          ->load($entity->bundle());
        $cdf->addAttribute('bundle_label', CDFAttribute::TYPE_STRING, $bundle_entity->label());
      }
    }
  }

}
