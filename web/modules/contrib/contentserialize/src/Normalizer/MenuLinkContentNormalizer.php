<?php

namespace Drupal\contentserialize\Normalizer;

use Drupal\Component\Uuid\Uuid;
use Drupal\contentserialize\Event\ImportEvents;
use Drupal\contentserialize\Event\MissingReferenceEvent;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizes/denormalizes menu_link_content entities into an array structure.
 *
 * It replaces serial IDs with UUIDs in entity links.
 */
class MenuLinkContentNormalizer extends UuidContentEntityNormalizer {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = MenuLinkContentInterface::class;

  /**
   * Create a menu link content normalizer.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    EventDispatcherInterface $event_dispatcher
  ) {
    parent::__construct($entity_manager);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);
    $data = $this->replaceLinkId($data, '\d+', function ($nid) {
      $node = Node::load($nid);
      if (!$node) {
        throw new UnexpectedValueException("Menu link content has link to non-existant node $nid");
      }
      return $node->uuid();
    });
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $menu_uuid = $data['uuid'][0]['value'];
    $data = $this->replaceLinkId($data, Uuid::VALID_PATTERN, function ($uuid) use ($menu_uuid, $context) {
      $results = \Drupal::entityQuery('node')
        ->condition('uuid', $uuid)
        ->execute();
      if (!$results) {
        // If it's not found dispatch an event and return an empty value.
        $event = new MissingReferenceEvent(
          'menu_link_content',
          $menu_uuid,
          'node',
          $uuid,
          function (MenuLinkContentInterface $entity, $target_id, $target_vid) {
            $entity->link = 'entity:node/' . $target_id;
          },
          $context
        );
        $this->eventDispatcher->dispatch(ImportEvents::MISSING_REFERENCE, $event);

        return '';
      }

      return current($results);
    });
    return parent::denormalize($data, $class, $format, $context);
  }

  /**
   * Replace the ID in an entity link.
   *
   * @param array $data
   *   The normalized array for the menu_link_content entity.
   * @param $pattern_fragment
   *   A regular expression fragment that will match the ID.
   * @param callable $callback
   *   A callable that accepts the ID currently in the link and returns the new
   *   ID to be used.
   *
   * @return array
   *   The updated normalized array for the menu_link_content_entity.
   */
  protected function replaceLinkId(array $data, $pattern_fragment, callable $callback) {
    $uri = preg_replace_callback('~^(entity:node/)(' . $pattern_fragment . ')$~', function ($matches) use ($callback) {
      return $matches[1] . $callback($matches[2]);
    }, $data['link'][0]['uri']);
    if ($uri == 'entity:node/') {
      $uri = 'route:<nolink>';
    }
    $data['link'][0]['uri'] = $uri;
    return $data;
  }

}
