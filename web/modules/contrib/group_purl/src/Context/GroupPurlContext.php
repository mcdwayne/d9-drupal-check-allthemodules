<?php

namespace Drupal\group_purl\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\purl\Event\ModifierMatchedEvent;
use Drupal\purl\PurlEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class GroupPurlContext.
 */
class GroupPurlContext implements ContextProviderInterface, EventSubscriberInterface {

  use StringTranslationTrait;
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var  \Drupal\purl\Event\ModifierMatchedEvent*/
  protected $modifierMatched;

  protected $contexts;
  /**
   * Constructs a new GroupContext object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PurlEvents::MODIFIER_MATCHED] = ['onModifierMatched'];

    return $events;
  }

  /**
   * This method is called whenever the purl.modifier_matched event is
   * dispatched.
   *
   * @param \Drupal\purl\Event\ModifierMatchedEvent $event
   */
  public function onModifierMatched(ModifierMatchedEvent $event) {
    //if (!in_array($event->getProvider(), ['group_purl_provider', 'group_purl_subdomain'])) {
      // We are not interested in modifiers not provided by this module.
    //  return;
    //}
    $this->modifierMatched = $event;
    $this->contexts[$event->getMethod()->getId()]  = $event->getModifier();
    ksort($this->contexts);
  }

  /**
   * {@inheritdoc}
   *
   * 3 different ways to get group context:
   * 1. Purl matched -- we are in a subdomain or subdirectory
   * 2. entity.group route
   * 3. entity.node.canonical route, with a purl_context set
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Create an optional context definition for group entities.
    $context_definition = EntityContextDefinition::fromEntityTypeId('group');
    $context_definition->setRequired(FALSE);
    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['group']);

    // Create a context from the definition and retrieved or created group.
    $context = new Context($context_definition, $this->getGroupFromRoute());
    $context->addCacheableDependency($cacheability);

    return ['group' => $context];
  }

  /**
   *
   */
  public function getGroupFromRoute() {
    if ($this->modifierMatched === NULL) {
      return;
    }
    $storage = $this->entityTypeManager->getStorage('group');
    $group = $storage->load($this->modifierMatched->getValue());
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Group from Purl'));
    return ['group' => $context];
  }


}
