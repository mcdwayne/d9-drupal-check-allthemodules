<?php

namespace Drupal\packages\Plugin\Condition;

use Drupal\packages\PackagesInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Package condition.
 *
 * @Condition(
 *   id = "package",
 *   label = @Translation("Package"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class Package extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The packages service..
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * Creates a new Package instance.
   *
   * @param \Drupal\packages\PackagesInterface $packages
   *   The packages service.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(PackagesInterface $packages, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('packages'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['package'] = [
      '#type' => 'select',
      '#title' => $this->t('When the user has the following package enabled and accessible'),
      '#default_value' => $this->configuration['package'],
      '#options' => [0 => $this->t('- None -')],
      '#attached' => ['library' => ['packages/packages.block.admin']],
    ];

    // Get list of packages.
    foreach ($this->packages->getPackageDefinitions() as $package_id => $definition) {
      $form['package']['#options'][$package_id] = $definition['label'];
    }

    // TODO: Option to negate nothing selected meaning access granted if the
    // user has no packages enabled.
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'package' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['package'] = $form_state->getValue('package');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->isNegated()) {
      return $this->t('The user does not have the package @package enabled', ['@package' => $this->configuration['package']]);
    }
    else {
      return $this->t('The user has the package @package enabled', ['@package' => $this->configuration['package']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['package'])) {
      return TRUE;
    }

    // Get the package state.
    return $this->packages->getState($this->configuration['package'])->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

}
