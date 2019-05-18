<?php

namespace Drupal\tr_rulez\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Makes the current path available as a context variable.
 */
class CurrentPathContext implements ContextProviderInterface {
  use StringTranslationTrait;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Constructs a new CurrentPathContext.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack service.
   */
  public function __construct(CurrentPathStack $current_path_stack) {
    $this->currentPathStack = $current_path_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $current_path = $this->currentPathStack->getPath();

    $context_definition = new ContextDefinition('string', $this->t('Current path'));
    $context = new Context($context_definition, $current_path);
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['url.path']);
    $context->addCacheableDependency($cacheability);

    $result = [
      'current_path' => $context,
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
