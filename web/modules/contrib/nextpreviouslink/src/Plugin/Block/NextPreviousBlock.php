<?php

namespace Drupal\nextpre\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Next Previous' block.
 *
 * @Block(
 *   id = "next_previous_block",
 *   admin_label = @Translation("Next Previous Block"),
 *   category = @Translation("Blocks")
 * )
 */
class NextPreviousBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * NextPreviousBlock class constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Symfony request stack instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager instance.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactory $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request_stack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the created time of the current node.
    if (!$this->request->attributes->get('node')) {
      return NULL;
    }
    $node = $this->request->attributes->get('node');
    $created_time = $node->get('vid')->getValue()[0]['value'];
    $link = "";
    $previous_link = $this->generatePrevious($created_time);
    $after_link = $this->generateNext($created_time);
    $link .= render($previous_link);
    $link .= render($after_link);
    return ['#markup' => $link];
  }

  /**
   * Cahce set none.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Lookup the previous node,youngest node which is still older than the node.
   */
  private function generatePrevious($created_time) {
    return $this->generateNextPrevious($created_time, 'prev');
  }

  /**
   * Lookup the next node,oldest node which is still younger than the node.
   */
  private function generateNext($created_time) {
    return $this->generateNextPrevious($created_time, 'next');
  }

  /**
   * Lookup the next or previous node.
   */
  private function generateNextPrevious($created_time, $direction = 'next') {
    if ($direction === 'next') {
      $comparison_opperator = '>';
      $sort = 'ASC';
      $display_text = $this->t('Next Post');
      $class = "blognext";
    }
    elseif ($direction === 'prev') {
      $comparison_opperator = '<';
      $sort = 'DESC';
      $display_text = $this->t('Previous Post');
      $class = "blogprevious";
    }
    $type = $this->configFactory->get('nextpre.settings')->get('nextpre_type');
    // Lookup 1 node younger (or older) than the current node.
    $query = $this->entityTypeManager->getStorage('node')->getQuery('AND');
    $next = $query->condition('vid', $created_time, $comparison_opperator)
      ->condition('type', $type)
      ->sort('vid', $sort)
      ->range(0, 1)
      ->execute();

    // If this is not the youngest (or oldest) node.
    if (!empty($next) && is_array($next)) {
      $next = array_values($next);
      $next = $next[0];

      // Find the alias of the next node.
      $nid = $next;
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], []);
      $link = Link::fromTextAndUrl($display_text, Url::fromUri('internal:/' . $url->getInternalPath()));
      $link = $link->toRenderable();
      $link['#attributes'] = ['class' => ['btn', $class]];
      return $link;
    }
  }

}
