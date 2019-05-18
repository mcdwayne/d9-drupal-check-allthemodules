<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Node ID freelinking plugin.
 *
 * Allows for a link link [[node:<nid>]], [[n:<nid>]], or [[node:<nid>]] to be
 * expanded to a link to the node with the title associated with that node ID.
 * A "could not find nid" message is displayed if the node could not be found.
 *
 * @Freelinking(
 *   id = "nid",
 *   title = @Translation("Node ID"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {  }
 * )
 */
class Node extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/(n(id|ode)?)$/A';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Click to view a local node');
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    // Failover.
    $link = [
      '#theme' => 'freelink_error',
      '#plugin' => 'nid',
    ];

    // Attempt to load the node by the node ID provided by target destination.
    $node = $this->entityTypeManager->getStorage('node')->load($target['dest']);

    if (NULL !== $node) {
      // Get target.
      if ($target['language']->getId() !== $node->language()->getId()) {
        $node = $node->getTranslation($target['language']->getId());
      }

      $link = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['language' => $target['language']]),
        '#attributes' => [
          'title' => $this->getTip(),
        ],
      ];
    }
    else {
      // Save some processing by generating the translation link for failover later.
      $link['#message'] = $this->t('Invalid node ID @nid', ['@nid' => $target['dest']]);
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

}
