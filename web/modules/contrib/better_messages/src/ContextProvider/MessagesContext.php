<?php

namespace Drupal\better_messages\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides current Drupal status messages as a context.
 */
class MessagesContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Current status messages.
   *
   * If this property is NULL, it means we haven't hit the rendering yet and
   * current messages can be queried from Drupal core. Otherwise the currently
   * rendered messages will be stored in here.
   *
   * @var array
   */
  protected $messages = NULL;

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];

    $context_definition = new ContextDefinition('map', NULL, FALSE);
    $context = new Context($context_definition, $this->getMessages());

    $cacheability = new CacheableMetadata();
    // We cannot cache this context as literally a few lines below some message
    // might be added.
    $cacheability->setCacheMaxAge(0);
    // Since messages are stored in session, we must vary by it.
    $cacheability->addCacheContexts(['session']);
    $context->addCacheableDependency($cacheability);

    $result['better_messages'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('map', $this->t('Current status messages')));
    return ['better_messages' => $context];
  }

  /**
   * Store currently rendered status messages.
   *
   * @param array $messages
   *   Messages array to store (supposedly it should be the currently rendered
   *   ones)
   */
  public function setMessages(array $messages) {
    $this->messages = $messages;
  }

  /**
   * Retrieve current status messages.
   *
   * @return array
   *   Array of current messages. It will either be a result of
   *   drupal_get_messages() or $this->messages should we already be beyond the
   *   phase of rendering status messages on the current page request
   */
  protected function getMessages() {
    return is_null($this->messages) ? drupal_get_messages(NULL, FALSE) : $this->messages;
  }

}
