<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TermParentCdfAttribute.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\CdfAttributes
 */
class TermParentCdfAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES => [
        ['onPopulateAttributes', 100],
      ],
    ];
  }

  /**
   * Reacts to POPULATE_CDF_ATTRIBUTES event.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   Event.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    /** @var \Drupal\taxonomy\Entity\Term $entity */
    $entity = $event->getEntity();
    if (FALSE == ($entity instanceof TermInterface)) {
      return;
    }

    $term_parents = $this->getStorage()->loadParents($entity->id());
    if (empty($term_parents)) {
      return;
    }

    $parents = array_map(function ($parent) {
      return $parent->uuid();
    }, $term_parents);

    // Exclude current term from parents list.
    $parents = array_filter($parents, function ($uuid) use ($entity) {
      return !empty($uuid) && $uuid !== $entity->uuid();
    });

    $event->getCdf()
      ->addAttribute('parent', CDFAttribute::TYPE_ARRAY_REFERENCE, array_values($parents));
  }

  /**
   * Gets the Taxonomy Term Storage.
   */
  protected function getStorage() {
    return $this->getEntityTypeManager()->getStorage('taxonomy_term');
  }

  /**
   * Gets the Entity Type Manager Service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The EntityTypeManager.
   */
  protected function getEntityTypeManager() {
    return \Drupal::entityTypeManager();
  }

}
