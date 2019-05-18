<?php

namespace Drupal\purest_metatag\Normalizer;

use Drupal\metatag\Normalizer\MetatagNormalizer;

/**
 * Normalizes metatag into the viewed entity.
 */
class PurestMetatagNormalizer extends MetatagNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    // @see metatag_get_tags_from_route()
    $entity = $field_item->getEntity();
    $tags = metatag_get_tags_from_route($entity);
    $schema = [];

    $normalized['value'] = [];
    if (isset($tags['#attached']['html_head'])) {
      foreach ($tags['#attached']['html_head'] as $key => $tag) {
        if (isset($tag[0]['#attributes']['schema_metatag'])) {
          $schema[] = $tag;
          unset($tags['#attached']['html_head'][$key]);
        }
        elseif (isset($tag[0]['#attributes']['content'])) {
          $normalized[$tag[1]] = $tag[0]['#attributes']['content'];
        }
        elseif (isset($tag[0]['#attributes']['href'])) {
          $normalized[$tag[1]] = $tag[0]['#attributes']['href'];
        }
      }
    }

    if (isset($context['langcode'])) {
      $normalized['lang'] = $context['langcode'];
    }

    // If schema metatags exists then prepare the output for json.
    if (!empty($schema)) {
      // Drupal\schema_metatag\SchemaMetatagManagerInterface.
      $schemeMetatagInterface = \Drupal::service('schema_metatag.schema_metatag_manager');
      $normalized['schema'] = $schemeMetatagInterface->parseJsonld($schema);
    }

    // Remove stray value property.
    if (isset($normalized['value']) && empty($normalized['value'])) {
      unset($normalized['value']);
    }

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return FALSE;
  }

}
