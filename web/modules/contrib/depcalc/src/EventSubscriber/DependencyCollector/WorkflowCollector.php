<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;

/**
 * Subscribes to dependency collection to extract the entity form display.
 */
class WorkflowCollector extends BaseDependencyCollector {

  /**
   * Moderation Information
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * EntityFormDisplayDependencyCollector constructor.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface|null $moderation_information
   *   The moderation information.
   */
  public function __construct(ModerationInformationInterface $moderation_information = NULL) {
    $this->moderationInfo = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates the associated workflows.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   *
   * @throws \Exception
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if (!$this->moderationInfo) {
      return;
    }
    if ($event->getEntity() instanceof ContentEntityInterface) {
      $workflow = $this->moderationInfo->getWorkflowForEntity($event->getEntity());
      if ($workflow) {
        $wrapper = new DependentEntityWrapper($workflow);
        $local_dependencies = [];
        $this->mergeDependencies($wrapper, $event->getStack(), $this->getCalculator()
          ->calculateDependencies($wrapper, $event->getStack(), $local_dependencies));
        $event->addDependency($wrapper);
      }
    }
  }

}
