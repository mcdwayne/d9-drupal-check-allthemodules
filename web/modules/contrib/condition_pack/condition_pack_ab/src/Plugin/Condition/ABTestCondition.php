<?php

namespace Drupal\condition_pack_ab\Plugin\Condition;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'A/B test' condition.
 *
 * @Condition(
 *   id = "abtest",
 *   label = @Translation("A/B test"),
 * )
 */
class ABTestCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface, CacheableDependencyInterface {

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
      'abtest' => '',
      'negate' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['abtest'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Page view percentage'),
      '#default_value' => $this->configuration['abtest'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->options()),
      '#description' => $this->t('Show content to a specific percentage of visitors. This condition will disable block cache for this block and may have performance implications.'),
      '#attached' => array(
        'library' => array(
          'condition_pack_ab/drupal.condition_pack_ab',
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['abtest'] = $form_state->getValue('abtest');
    if ($this->configuration['abtest'] == 'none') {
      $this->configuration['abtest'] = '';
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $value = $this->configuration['abtest'];
    return $this->t('Shown on @value% of page views', array('@value' => ((float) $value * 100)));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $value = $this->configuration['abtest'];
    if (empty($value)) {
      return TRUE;
    }
    $check = rand(1, 100);
    $value = (int) $value;
    if ($check <= $value) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // A-B tests cannot be cached.
    return 0;
  }

  /**
   * @inheritdoc
   */
  public function options() {
    $options['none'] = $this->t('Disabled');
    for ($i = 1; $i < 20; $i++) {
      $j = $i*5;
      $options[$j] = $j . '%';
    }
    return $options;
  }
}
