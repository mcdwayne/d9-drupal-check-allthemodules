<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\drd\RemoteActions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'WidgetGlobalActions' block.
 *
 * @Block(
 *  id = "drd_global_actions",
 *  admin_label = @Translation("DRD Global actions"),
 *  weight = -5,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetGlobalActions extends WidgetBase {

  /**
   * Drupal\drd\RemoteActions definition.
   *
   * @var \Drupal\drd\RemoteActions
   */
  protected $drdRemoteActions;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\drd\RemoteActions $drd_remote_actions
   *   The service handling remote actions.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        array $plugin_definition,
        RemoteActions $drd_remote_actions
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->drdRemoteActions = $drd_remote_actions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('drd.remote.actions')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Action');
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    return \Drupal::formBuilder()->getForm('Drupal\drd\Form\Actions');
  }

}
