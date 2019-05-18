<?php

namespace Drupal\purest_menus\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\Component\Utility\UrlHelper;

/**
 * MenuLinkTreeElement Normalizer.
 */
class MenuLinkNormalizer extends NormalizerBase {
  protected $supportedInterfaceOrClass = 'Drupal\Core\Menu\MenuLinkInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $base_fields = [];
    $custom_fields = [];
    $url = $object->getUrlObject()->toString();
    $uuid = $object->getDerivativeId();
    $entity = \Drupal::service('entity.repository')
      ->loadEntityByUuid('menu_link_content', $uuid);
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('menu_link_content', 'content');

    // Get all base fields.
    $output = [
      'id' => (int) $entity->id(),
      'langcode' => $entity->language()->getId(),
      'default_langcode' => $entity->language()->isDefault(),
      'uuid' => $uuid,
      'weight' => (int) $object->getWeight(),
      'title' => $object->getTitle(),
      'description' => $object->getDescription(),
      'menu_name' => $object->getMenuName(),
      'bundle' => $object->getProvider(),
      'parent' => $object->getParent() ? (int) $object->getParent() : NULL,
      'enabled' => $object->isEnabled(),
      'expanded' => $object->isExpanded(),
      'rediscover' => intval($entity->requiresRediscovery()) ? TRUE : FALSE,
      'link' => $url,
      'changed' => intval($entity->getChangedTime()),
      'external' => UrlHelper::isExternal($url),
      'purest_menus_in_active_trail' => FALSE,
      'purest_menus_has_subtree' => FALSE,
      'purest_menus_subtree' => [],
    ];

    // Load any custom field values.
    foreach ($field_definitions as $key => $value) {
      if (!isset($output[$key])) {
        $output[$key] = $entity->get($key)->value;
      }
    }

    return $output;
  }

}
