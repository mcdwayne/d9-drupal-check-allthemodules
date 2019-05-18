<?php
/**
 * @file
 * Contains \Drupal\collect_test\Plugin\collect\Processor\ContextCollector.
 */

namespace Drupal\collect_test\Plugin\collect\Processor;

use Drupal\collect\Processor\ProcessorBase;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processor that makes the processor context accessible by tests.
 *
 * @Processor(
 *   id = "context_collector",
 *   label = @Translation("Context collector"),
 *   description = @Translation("Collects the processing context for tests.")
 * )
 */
class ContextCollector extends ProcessorBase implements ContainerFactoryPluginInterface {

  /**
   * State key for the context.
   */
  const STATE_KEY = 'collect_test.context_collector';

  /**
   * The injected site state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, TypedDataProvider $typed_data_provider, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $typed_data_provider);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('logger.factory')->get('collect'),
      $container->get('collect.typed_data_provider'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(CollectDataInterface $data, array &$context) {
    $this->state->set(static::STATE_KEY, serialize($context));
  }

  /**
   * Returns the processor context.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The site state.
   *
   * @return array
   *   The processor context as it was when the processor was applied.
   */
  public static function getContext(StateInterface $state) {
    return unserialize($state->get(static::STATE_KEY));
  }

}
