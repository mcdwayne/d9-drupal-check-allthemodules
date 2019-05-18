<?php

namespace Drupal\chatbot_api\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Intent Plugin plugins.
 */
abstract class IntentPluginBase extends PluginBase implements IntentPluginInterface {

  /**
   * The response.
   *
   * @var \Drupal\chatbot_api\IntentResponseInterface
   */
  protected $response;

  /**
   * The response.
   *
   * @var \Drupal\chatbot_api\IntentRequestInterface
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->response = $this->configuration['response'];
    $this->request = $this->configuration['request'];
  }

}
