<?php

namespace Drupal\commerce_condition_kit_domain\Plugin\Commerce\Condition;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Domain condition for Commerce.
 *
 * @CommerceCondition(
 *   id = "domain",
 *   label = @Translation("Domain"),
 *   display_label = @Translation("Limit by domain"),
 *   category = @Translation("Domain"),
 *   entity_type = "commerce_order",
 * )
 */
class Domain extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The domain condition.
   *
   * @var \Drupal\domain\Plugin\Condition\Domain
   */
  protected $domainCondition;

  /**
   * Constructs a new Domain object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionManager $condition_manager, ContextRepositoryInterface $context_repository, ContextHandlerInterface $context_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contextRepository = $context_repository;
    $this->contextHandler = $context_handler;
    $this->conditionManager = $condition_manager;
    $this->domainCondition = $this->conditionManager->createInstance('domain');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('context.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'domains' => [],
      'negate' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());
    $this->domainCondition->setConfiguration($this->getConfiguration());
    $form += $this->domainCondition->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);

    $domain_default_config = $this->domainCondition->defaultConfiguration();

    foreach (array_keys($domain_default_config) as $item) {
      if (isset($values[$item])) {
        $values[$item] = is_array($values[$item])
          ? array_filter($values[$item])
          : $values[$item];
        $this->configuration[$item] = $values[$item];
      }
    }

    if (isset($values['context_mapping'])) {
      $this->domainCondition->setContextMapping($values['context_mapping']);
      $context_mapping = $this->domainCondition->getConfiguration()['context_mapping'];
      $this->configuration['context_mapping'] = $context_mapping;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->domainCondition->setConfiguration($this->getConfiguration());

    $context_mapping = array_values($this->domainCondition->getContextMapping());
    $contexts = $this->contextRepository->getRuntimeContexts($context_mapping);
    $this->contextHandler->applyContextMapping($this->domainCondition, $contexts);

    return $this->conditionManager->execute($this->domainCondition);
  }

}
