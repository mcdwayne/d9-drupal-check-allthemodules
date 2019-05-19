<?php

namespace Drupal\simple_seo_preview;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SimpleSeoPreviewManager.
 *
 * @package Drupal\simple_seo_preview
 */
class SimpleSeoPreviewManager implements SimpleSeoPreviewManagerInterface {

  /**
   * The Simple SEO preview logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * SimpleSeoPreviewManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channel_factory
   *   The LoggerChannelFactoryInterface object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The RouteMatchInterface object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The EventDispatcherInterface object.
   */
  public function __construct(
    LoggerChannelFactoryInterface $channel_factory,
    RouteMatchInterface $route_match,
    EventDispatcherInterface $event_dispatcher) {
    $this->logger = $channel_factory->get('simple_seo_preview');
    $this->routeMatch = $route_match;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Returns a list of fields handled by Simple SEO preview.
   *
   * @return array
   *   A list of supported field types.
   */
  protected function fieldTypes() {
    return [
      'simple_seo_preview',
    ];
  }

  /**
   * Returns a list of the Simple SEO preview fields on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to examine.
   *
   * @return array
   *   The fields from the entity which are Simple SEO preview fields.
   */
  public function getFields(ContentEntityInterface $entity) {
    $field_list = [];

    if ($entity instanceof ContentEntityInterface) {
      // Get a list of the simple_seo_preview field types.
      $field_types = $this->fieldTypes();

      // Get a list of the field definitions on this entity.
      $definitions = $entity->getFieldDefinitions();

      // Iterate through all the fields looking for ones in our list.
      foreach ($definitions as $field_name => $definition) {
        // Get the field type, ie: simple_seo_preview.
        $field_type = $definition->getType();

        // Check the field type against our list of fields.
        if (isset($field_type) && in_array($field_type, $field_types)) {
          $field_list[$field_name] = $definition;
        }
      }
    }

    return $field_list;
  }

  /**
   * Get a node list of tags.
   *
   * @param \Drupal\node\NodeInterface $node
   *   NodeInterface object.
   * @param string $field_name
   *   Field machine name.
   *
   * @return array|mixed
   *   List of meta tags.
   */
  protected function getFieldTags(NodeInterface $node, $field_name) {
    $tags = [];
    foreach ($node->{$field_name} as $item) {
      // Get serialized value and break it into an array of tags with values.
      $serialized_values = $item->get('value')->getValue();
      if (!empty($serialized_values)) {
        $values = unserialize($serialized_values);
        if (isset($values['meta'])) {
          $tags = $values['meta'];
        }
      }
    }
    return $tags;
  }

  /**
   * Get current node.
   *
   * @return \Drupal\node\Entity\Node|null
   *   Route node.
   */
  public function getNode() {
    if ($this->routeMatch->getRouteName() === 'entity.node.canonical') {
      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof Node) {
        return $node;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function tagsFromNode(NodeInterface $node) {
    $tags = [];

    $fields = $this->getFields($node);

    /* @var \Drupal\field\Entity\FieldConfig $field_info */
    foreach ($fields as $field_name => $field_info) {
      // Get the tags from this field.
      $tags = $this->getFieldTags($node, $field_name);
    }
    return $tags;
  }

  /**
   * Generate elements array.
   *
   * @return array
   *   Elements.
   */
  public function generateElements() {
    $elements = [];
    if ($node = $this->getNode()) {
      $tags = $this->tagsFromNode($node);
      foreach ($tags as $name => $content) {
        if (!empty($content)) {
          $this->generateElement($elements, 'meta', [
            'name'    => $name,
            'content' => $content,
          ]);
        }
      }
    }
    return $elements;
  }

  /**
   * Generate attached element.
   *
   * @param array $elements
   *   Elements array.
   * @param string $tag
   *   Tag.
   * @param array $attributes
   *   Attributes.
   */
  public function generateElement(array &$elements, $tag, array $attributes) {
    $elements['#attached']['html_head'][] = [
      [
        '#tag'        => $tag,
        '#attributes' => $attributes,
      ],
      $attributes['name'],
    ];
  }

}
