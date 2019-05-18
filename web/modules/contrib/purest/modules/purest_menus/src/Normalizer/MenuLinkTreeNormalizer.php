<?php

namespace Drupal\purest_menus\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * MenuLinkTreeElement Normalizer.
 */
class MenuLinkTreeNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = 'Drupal\Core\Menu\MenuLinkTreeElement';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $config = \Drupal::service('config.factory')->get('purest_menus.settings');
    $field_options = $config->get('menu_item_fields');

    $output = $this->serializer->normalize($object->link, $format, $context);

    // @todo Make purest_menus_ fields computed fields.
    foreach ($output as $key => $field) {
      switch ($key) {
        case 'purest_menus_in_active_trail':
          $output[$key] = $object->inActiveTrail;
          break;

        case 'purest_menus_has_subtree':
          $output[$key] = $object->hasChildren;
          break;

        case 'purest_menus_subtree':
          $output[$key] = $this->serializer
            ->normalize($object->subtree, $format, $context);
          break;
      }
    }

    if ($field_options === NULL) {
      return $output;
    }

    // Apply config options to each field.
    foreach ($field_options as $key => $option) {
      if ($option['exclude'] || ($option['hide_empty'] &&
        ($output[$key] === NULL || empty($output[$key])))) {
        unset($output[$key]);
      }
      elseif ($option['custom_label']) {
        $output[$option['custom_label']] = $output[$key];
        unset($output[$key]);
      }
    }

    return $output;
  }

}
