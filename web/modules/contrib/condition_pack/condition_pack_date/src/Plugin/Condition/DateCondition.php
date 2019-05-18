<?php

namespace Drupal\condition_pack_date\Plugin\Condition;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a date-sensitive condition base.
 *
 * @Condition(
 *   id = "date",
 *   label = @Translation("Date, after"),
 * )
 */
class DateCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface, CacheableDependencyInterface {

  protected $variable = 'date';

  public function title() {
   return $this->t('Date, after');
  }

  public function description() {
    return $this->t('Display on or after the listed date. Enter the date in a format recognized by PHP strtotime().');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      $this->variable => '',
      'negate' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->variable] = array(
      '#type' => 'textfield',
      '#title' => $this->title(),
      '#default_value' => $this->configuration[$this->variable],
      '#description' => $this->description(),
      '#attached' => array(
        'library' => array(
          'condition_pack_date/drupal.condition_pack_date',
        ),
      ),
    );
    $form = parent::buildConfigurationForm($form, $form_state);
    // This condition cannot be negated.
    $form['negate']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($value = $form_state->getValue($this->variable)) {
      $date = strtotime($value);
      if (!$date) {
        $form_state->setErrorByName($this->variable, $this->t('Invalid date format.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration[$this->variable] = $form_state->getValue($this->variable);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $value = $this->configuration[$this->variable];
    return $this->t('Shown on or after @value', array('@value' => implode(', ', $value)));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $value = $this->configuration[$this->variable];
    if (empty($value) && !$this->isNegated()) {
      return TRUE;
    }
    $value = (int) date('U', strtotime($value));
    $today = (int) date('U');

    $before = $this->variable == 'date_before';

    if ($before) {
      return $value < $today;
    }
    return $today >= $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Calculate the cache based on evaluation.
    $access = $this->evaluate();
    $before = $this->variable == 'date_before';
    $value = $this->configuration[$this->variable];
    $value = date('U', strtotime($value));
    // Have we passed the date? Cache forever.
    if ($access && !$before) {
      $max_age = -1;
    }
    // Not before but not accessible means we have not reached the date yet.
    elseif (!$access && !$before) {
      $max_age = $value - REQUEST_TIME;
    }
    // Before date but accessible means we have not reached the date yet.
    elseif ($access && $before) {
      $max_age = $value - REQUEST_TIME;
    }
    // Before but not accessible means the date has passed.
    elseif (!$access && $before) {
      $max_age = -1;
    }

    return $max_age;
  }

}
