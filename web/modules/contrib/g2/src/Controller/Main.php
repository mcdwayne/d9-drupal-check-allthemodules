<?php

/**
 * @file
 * Contains the G2 Main page controller.
 */

namespace Drupal\g2\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Drupal\g2\Alphabar;
use Drupal\g2\G2;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Main contains the G2 main page controller.
 */
class Main implements ContainerInjectionInterface {
  /**
   * Title of the G2 main page.
   */
  const TITLE = 'G2 glossary main page';

  /**
   * The g2.alphabar service.
   *
   * @var \Drupal\g2\Alphabar
   */
  protected $alphabar;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Main constructor.
   *
   * @param \Drupal\g2\Alphabar $alphabar
   *   The g2.alphabar service.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    Alphabar $alphabar, ImmutableConfig $config) {
    $this->alphabar = $alphabar;
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * The controller for the G2 main page.
   *
   * @return array
   *   A render array.
   */
  public function indexAction() {
    $alphabar = [
      '#theme' => 'g2_alphabar',
      '#alphabar' => $this->alphabar->getLinks(),
      // Set Row_length so that only an extremely long alphabar would wrap.
      '#row_length' => 2 << 16,
    ];

    $generator = $this->config->get('controller.main.nid');
    $node = Node::load($generator);
    if ($node instanceof NodeInterface) {
      $title = $node->label();

      // @TODO Ensure we still want to override the site name.
      /* _g2_override_site_name(); */

      if (!$node->body->isEmpty()) {
        // Simulate publishing.
        $node->setPublished(NODE_PUBLISHED);
        // Remove the title : we used it for the page title.
        $node->setTitle(NULL);
        $builder = $this->entityTypeManager->getViewBuilder($node->getEntityTypeId());
        $text = $builder->view($node);
      }
      else {
        // Empty or missing body field.
        $text = [];
      }
    }
    else {
      // Node not found.
      $text = [];
    }

    $ret = array(
      '#theme' => 'g2_main',
      '#alphabar' => $alphabar,
      '#text' => $text,
    );

    if (!empty($title)) {
      $ret['#title'] = $title;
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\g2\Alphabar $alphabar */
    $alphabar = $container->get('g2.alphabar');

    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');

    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /* @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $config_factory->get(G2::CONFIG_NAME);

    return new static($entity_type_manager, $alphabar, $config);
  }

}
