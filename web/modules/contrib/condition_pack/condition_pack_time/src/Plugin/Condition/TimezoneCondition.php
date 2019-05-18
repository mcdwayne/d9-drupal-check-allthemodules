<?php

namespace Drupal\condition_pack_time\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Timezone' condition.
 *
 * @Condition(
 *   id = "timezone",
 *   label = @Translation("Timezone"),
 * )
 */
class TimezoneCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

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
      'timezone' => [],
      'negate' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['timezone'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 10,
      '#title' => $this->t('User timezone'),
      '#default_value' => $this->configuration['timezone'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->options()),
      '#description' => $this->t('Show content for users in matching timezones.'),
      '#attached' => array(
        'library' => array(
          'condition_pack_time/drupal.condition_pack_timezone',
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
    $this->configuration['timezone'] = array_filter($form_state->getValue('timezone'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $value = $this->configuration['timezone'];
    return $this->t('Shown for users in @value', array('@value' => implode(', ', $value)));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $value = $this->configuration['timezone'];
    if (empty($value) && !$this->isNegated()) {
      return TRUE;
    }
    $timezone = drupal_get_user_timezone();

    // Simple name match.
    if (in_array($timezone, $value)) {
      return TRUE;
    }
    $dateTime = new \DateTime();
    $dateTime->setTimeZone(new \DateTimeZone($timezone));
    $tz =  $dateTime->format('O');
    foreach ($value as $item) {
      $dateTime = new \DateTime();
      $dateTime->setTimeZone(new \DateTimeZone($item));
      $zone = $dateTime->format('O');
      if ($zone == $tz) {
        return TRUE;
      }
    }
    // TODO: checking logic.
    // NOTE: The context system handles negation for us.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'timezone';
    return $contexts;
  }

  /**
   * @inheritdoc
   */
  public function options() {
    return system_time_zones();
  }

}
