<?php

namespace Drupal\condition_pack_time\Plugin\Condition;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Day of the week' condition.
 *
 * @Condition(
 *   id = "time",
 *   label = @Translation("Time of day"),
 * )
 */
class TimeCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface, CacheableDependencyInterface {

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
      'time' => [],
      'negate' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['time'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Hour of the day'),
      '#default_value' => $this->configuration['time'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->options()),
      '#description' => $this->t('Show content during selected hours of the day.'),
      '#attached' => array(
        'library' => array(
          'condition_pack_time/drupal.condition_pack_time',
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
    $this->configuration['time'] = array_filter($form_state->getValue('time'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $value = $this->configuration['time'];
    return $this->t('Shown on @value', array('@value' => implode(', ', $value)));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $value = $this->configuration['time'];
    if (empty($value) && !$this->isNegated()) {
      return TRUE;
    }
    // @TODO: user timezone?
    $hour = date('H', REQUEST_TIME);
    // NOTE: The context system handles negation for us.
    return (bool) in_array($hour, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache until the next hour.
    // @TODO: fancier / longer caching based on settings.
    return 3600 - (REQUEST_TIME % 3600);
  }

  /**
   * @inheritdoc
   */
  public function options() {
    $options = [];
    for ($i = 0; $i <= 23; $i++) {
      $options[$i] = $i . ':00';
    }
    return $options;
  }

}
