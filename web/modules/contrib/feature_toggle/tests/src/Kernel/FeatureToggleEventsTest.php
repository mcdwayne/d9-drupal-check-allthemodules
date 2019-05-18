<?php

namespace Drupal\Tests\feature_toggle\Kernel;

use Drupal\feature_toggle\Event\FeatureUpdateEvent;
use Drupal\feature_toggle\Event\FeatureUpdateEvents;
use Drupal\feature_toggle\Feature;
use Drupal\feature_toggle\FeatureInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Feature Toggle Events integration.
 *
 * @group feature_toggle
 */
class FeatureToggleEventsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feature_toggle'];

  /**
   * State service for recording information received by event listeners.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The feature manager service.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * The feature status service.
   *
   * @var \Drupal\feature_toggle\FeatureStatusInterface
   */
  protected $featureStatus;

  /**
   * The 1st feature.
   *
   * @var \Drupal\feature_toggle\FeatureInterface
   */
  protected $feature1;

  /**
   * The 2nd feature.
   *
   * @var \Drupal\feature_toggle\FeatureInterface
   */
  protected $feature2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->state = \Drupal::state();
    $this->featureManager = $this->container->get('feature_toggle.feature_manager');
    $this->featureStatus = $this->container->get('feature_toggle.feature_status');

    $this->feature1 = new Feature('feature1', $this->randomMachineName());
    $this->feature2 = new Feature('feature2', $this->randomMachineName());

    $this->featureManager->addFeature($this->feature1);
    $this->featureManager->addFeature($this->feature2);

    \Drupal::service('event_dispatcher')->addListener(FeatureUpdateEvents::UPDATE,
      [$this, 'genericEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(FeatureUpdateEvents::UPDATE . '.' . $this->feature1->name(),
      [$this, 'featureEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(FeatureUpdateEvents::UPDATE . '.' . $this->feature2->name(),
      [$this, 'featureEventRecorder']);
  }

  /**
   * Tests feature events.
   */
  public function testFeatureEvent() {
    $this->assertEvent($this->feature1, $this->feature2);
    $this->clearVariables();
    $this->assertEvent($this->feature2, $this->feature1);
  }

  /**
   * Generic event subscriber.
   *
   * @param \Drupal\feature_toggle\Event\FeatureUpdateEvent $event
   *   The event object.
   * @param string $name
   *   The event name.
   */
  public function genericEventRecorder(FeatureUpdateEvent $event, $name) {
    $this->state->set('feature_toggle_events_test.generic_event', [
      'event_name' => $name,
      'name' => $event->feature()->name(),
      'status' => $event->status(),
    ]);
  }

  /**
   * Feature specific event subscriber.
   *
   * @param \Drupal\feature_toggle\Event\FeatureUpdateEvent $event
   *   The event object.
   * @param string $name
   *   The event name.
   */
  public function featureEventRecorder(FeatureUpdateEvent $event, $name) {
    $this->state->set('feature_toggle_events_test.' . $event->feature()->name() . '_event', [
      'event_name' => $name,
      'name' => $event->feature()->name(),
      'status' => $event->status(),
    ]);
  }

  /**
   * Asserts Feature Event behavior.
   *
   * @param \Drupal\feature_toggle\FeatureInterface $modified_feature
   *   The feature triggeringthe event.
   * @param \Drupal\feature_toggle\FeatureInterface $other_feature
   *   Other non-modified feature.
   */
  protected function assertEvent(FeatureInterface $modified_feature, FeatureInterface $other_feature) {
    $this->featureStatus->setStatus($modified_feature, TRUE);
    // Validate generic event.
    $event = $this->state->get('feature_toggle_events_test.generic_event', []);
    $this->assertSame($event['event_name'], FeatureUpdateEvents::UPDATE);
    $this->assertSame($event['name'], $modified_feature->name());
    $this->assertSame($event['status'], TRUE);

    // Validate modified feature event.
    $event = $this->state->get('feature_toggle_events_test.' . $modified_feature->name() . '_event', []);
    $this->assertSame($event['event_name'], FeatureUpdateEvents::UPDATE . '.' . $modified_feature->name());
    $this->assertSame($event['name'], $modified_feature->name());
    $this->assertSame($event['status'], TRUE);

    // Validate not modified feature event.
    $event = $this->state->get('feature_toggle_events_test.' . $other_feature->name() . '_event', []);
    $this->assertSame($event, []);

    $this->featureStatus->setStatus($modified_feature, FALSE);
    // Validate generic event.
    $event = $this->state->get('feature_toggle_events_test.generic_event', []);
    $this->assertSame($event['event_name'], FeatureUpdateEvents::UPDATE);
    $this->assertSame($event['name'], $modified_feature->name());
    $this->assertSame($event['status'], FALSE);

    // Validate modified feature event.
    $event = $this->state->get('feature_toggle_events_test.' . $modified_feature->name() . '_event', []);
    $this->assertSame($event['event_name'], FeatureUpdateEvents::UPDATE . '.' . $modified_feature->name());
    $this->assertSame($event['name'], $modified_feature->name());
    $this->assertSame($event['status'], FALSE);

    // Validate not modified feature event.
    $event = $this->state->get('feature_toggle_events_test.' . $other_feature->name() . '_event', []);
    $this->assertSame($event, []);
  }

  /**
   * Resets state variables tracking events.
   */
  protected function clearVariables() {
    $this->state->set('feature_toggle_events_test.generic_event', []);
    $this->state->set('feature_toggle_events_test.feature2_event', []);
    $this->state->set('feature_toggle_events_test.feature1_event', []);
  }

}
