<?php

/**
 * @file
 * Contains \Drupal\expressions\Plugin\Condition\Expression.
 */

namespace Drupal\expressions\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\expressions\ExpressionLanguage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Expression' condition.
 *
 * @Condition(
 *   id = "expression",
 *   label = @Translation("Expression"),
 * )
 */
class Expression extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Expression language service.
   *
   * @var ExpressionLanguage
   */
  protected $language;

  /**
   * Constructs an expression condition plugin.
   *
   * @param  \Drupal\expressions\ExpressionLanguage $language
   *   An alias manager to find the alias for the current system path.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ExpressionLanguage $language, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->language = $language;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('expressions.language'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['expression' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['expression'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Expression'),
      '#default_value' => $this->configuration['expression'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['expression'] = $form_state->getValue('expression');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->configuration['expression'] ?
      $this->t('Expression is set.') : $this->t('Expression is not set.');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // @TODO: Process tokens.
    $expression = $this->configuration['expression'];
    return $this->language->evaluate($expression);
  }

}
