<?php

namespace Drupal\say_hello_dialogflow\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\say_hello_dialogflow\SayHelloDialogflow as SayHelloDialogflowService;

/**
 * Say Hello Dialogflow block.
 *
 * @Block(
 *  id = "say_hello_dialogflow_block",
 *  admin_label = @Translation("Say Hello Dialogflow"),
 * )
 */
class SayHelloDialogflowBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\say_hello_dialogflow\SayHelloDialogflow definition.
   *
   * @var \Drupal\say_hello_dialogflow\SayHelloDialogflow
   */
  protected $dialogflow;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\say_hello_dialogflow\SayHelloDialogflow $dialogflow
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SayHelloDialogflowService $dialogflow) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dialogflow = $dialogflow;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('say_hello_dialogflow.dialogflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build[]['dialogflow'] = [
      '#lazy_builder' => ['say_hello_dialogflow.lazy_builder:renderDialogflow', []],
      '#create_placeholder' => TRUE,
    ];

    return $build;
  }
}
