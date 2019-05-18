<?php

namespace Drupal\condition_pack_date\Plugin\Condition;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Day of the week' condition.
 *
 * @Condition(
 *   id = "day",
 *   label = @Translation("Day of the week"),
 * )
 */
class DayCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface, CacheableDependencyInterface {

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
      'day' => [],
      'negate' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['day'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Day of the week'),
      '#default_value' => $this->configuration['day'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->options()),
      '#description' => $this->t('Show content on selected days of the week.'),
      '#attached' => array(
        'library' => array(
          'condition_pack_date/drupal.condition_pack_day',
        ),
      ),
    );
    $form = parent::buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['day'] = array_filter($form_state->getValue('day'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $value = $this->configuration['day'];
    return $this->t('Shown on @value', array('@value' => implode(', ', $value)));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $value = $this->configuration['day'];
    if (empty($value) && !$this->isNegated()) {
      return TRUE;
    }
    $today = date('D');
    // NOTE: The context system handles negation for us.
    return (bool) in_array($today, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache until midnight.
    return date('U', strtotime('12:00AM tomorrow')) - REQUEST_TIME;
  }

  /**
   * @inheritdoc
   */
  public function options() {
    return [
      'Sun' => $this->t('Sunday'),
      'Mon' => $this->t('Monday'),
      'Tue' => $this->t('Tuesday'),
      'Web' => $this->t('Wednesday'),
      'Thu' => $this->t('Thursday'),
      'Fri' => $this->t('Friday'),
      'Sat' => $this->t('Saturday'),
    ];
  }

}
