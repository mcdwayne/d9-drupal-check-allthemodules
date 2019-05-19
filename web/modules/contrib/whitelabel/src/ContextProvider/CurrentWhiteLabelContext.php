<?php

namespace Drupal\whitelabel\ContextProvider;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\whitelabel\WhiteLabelProviderInterface;

/**
 * Sets the current white label as a context.
 */
class CurrentWhiteLabelContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The current white label.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * CurrentWhiteLabelContext constructor.
   *
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   */
  public function __construct(WhiteLabelProviderInterface $white_label_provider) {
    $this->whiteLabelProvider = $white_label_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:whitelabel', NULL, FALSE);
    $value = NULL;

    if ($white_label = $this->whiteLabelProvider->getWhiteLabel()) {
      $value = $white_label;
    }

    $context = new Context($context_definition, $value);
    if ($white_label) {
      $context->addCacheableDependency($white_label);
    }
    $result['whitelabel'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:whitelabel', $this->t('Active white label')));
    return ['whitelabel' => $context];
  }

}
