<?php

namespace Drupal\contacts_events\Plugin\RulesDataProcessor;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\PluginBase;
use Drupal\rules\Context\DataProcessorInterface;
use Drupal\rules\Engine\ExecutionStateInterface;

/**
 * A data processor for generate dynamic dates.
 *
 * The plugin configuration may contain the following entries:
 * - add: TRUE to add, FALSE to subtract.
 * - interval: The interval definition as per DateInterval.
 * - normalize: Normalize the time, required for date only comparisons.
 *
 * @RulesDataProcessor(
 *   id = "rules_dynamic_date",
 *   label = @Translation("Convert a dynamic date.")
 * )
 */
class DynamicDate extends PluginBase implements DataProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += [
      'add' => FALSE,
      'interval' => NULL,
      'normalize' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, ExecutionStateInterface $rules_state) {
    // Attempt to convert the date.
    if (is_int($value)) {
      $value = DrupalDateTime::createFromTimestamp($value);
    }
    elseif (is_string($value)) {
      $value = DrupalDateTime::createFromTimestamp(strtotime($value));
    }
    elseif ($value instanceof \DateTime) {
      $value = DrupalDateTime::createFromDateTime($value);
    }
    // Clone a DrupalDateTime so we aren't modifying an existing item.
    elseif ($value instanceof DrupalDateTime) {
      $value = clone $value;
    }
    // If we haven't found something suitable, throw an exception.
    else {
      throw new ContextException('Invalid date time.');
    }

    // If we have an interval, apply it.
    if ($this->configuration['interval']) {
      $interval = new \DateInterval($this->configuration['interval']);
      if ($this->configuration['add']) {
        $value->add($interval);
      }
      else {
        $value->sub($interval);
      }
    }

    // Normalize the time if requested.
    if ($this->configuration['normalize']) {
      $value->setDefaultDateTime();
    }

    return $value;
  }

}
