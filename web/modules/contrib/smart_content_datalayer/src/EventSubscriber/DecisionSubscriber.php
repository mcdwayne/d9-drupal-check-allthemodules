<?php

namespace Drupal\smart_content_datalayer\EventSubscriber;

use Drupal\smart_content\Event\DecisionEvent;
use Drupal\smart_content\Variation\VariationInterface;
use Drupal\smart_content_datalayer\Ajax\DataLayerCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the winner selected event.
 *
 * @package Drupal\smart_content_datalayer\EventSubscriber
 */
class DecisionSubscriber implements EventSubscriberInterface {

  /**
   * The recursion limit for condition arrays.
   *
   * @var int
   */
  const RECURSION_LIMIT = 5;

  /**
   * Counter for recursive methods.
   *
   * @var int
   */
  private $counter;

  /**
   * DecisionSubscriber constructor.
   */
  public function __construct() {
    $this->counter = 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DecisionEvent::EVENT_NAME => 'decisionHandler',
    ];
  }

  /**
   * Event handler for the winner selected event.
   *
   * @param \Drupal\smart_content\Event\DecisionEvent $event
   */
  public function decisionHandler(DecisionEvent $event) {
    // Get the response object.
    $response = $event->getResponse();
    // Get the winning variation.
    $winner = $event->getVariation();
    // Parse out required details from the variation.
    $data = $this->getVariationData($winner);
    $response->addCommand(new DataLayerCommand($data));
  }

  /**
   * Gets variation ID, condition and reaction settings for the specified
   * variation.
   *
   * @param \Drupal\smart_content\Variation\VariationInterface $variation
   *   The winning
   *
   * @return array
   */
  protected function getVariationData(VariationInterface $variation) {
    $configuration = $variation->getConfiguration();
    return [
      'variation_id' => $this->getVariationId($configuration['id']),
      'conditions' => $this->getConditions($configuration['conditions_settings']),
      'reactions' => $this->getReactions($configuration['reactions_settings']),
    ];
  }

  /**
   * Gets the variation number.
   *
   * @param string $id
   *   The winning variation ID.
   *
   * @return string
   *   The variation number.
   */
  protected function getVariationId($id) {
    $variation_id = 'Variation ';
    $variation_id .= $id === 'variation_block' ? 1 :
      ((int) str_replace('variation_block_', '', $id));
    return $variation_id;
  }

  /**
   * Gets the conditions contained within the winning variation.
   *
   * @param array $conditions_settings
   *   Details for all the conditions contained within the winning variation.
   *
   * @return array
   *   A flat array of the conditions that were met.
   */
  protected function getConditions(array $conditions_settings) {
    $conditions = [];
    foreach ($conditions_settings as $settings) {
      if ($this->counter < static::RECURSION_LIMIT
        && $settings['plugin_id'] == 'group') {
        $conditions = array_merge($conditions, $this->getConditions($settings['conditions_settings']));
      }
      else {
        $conditions[] = [
          'id' => $settings['id'],
          'settings' => array_key_exists('conditions_type_settings', $settings) ?
            $settings['conditions_type_settings'] : NULL,
        ];
      }
    }
    $this->counter++;
    return $conditions;
  }

  /**
   * Gets the reactions that were displayed.
   *
   * @param array $reactions_settings
   *   Details for all the reactions that were displayed.
   *
   * @return array
   *   A flat array of the reaction block labels.
   */
  protected function getReactions(array $reactions_settings) {
    $reactions = [];
    foreach ($reactions_settings as $settings) {
      // Block specific handling.
      if ($settings['plugin_id'] === 'block') {
        $reactions[] = $settings['block_instance']['label'];
      }
    }
    return $reactions;
  }

}
