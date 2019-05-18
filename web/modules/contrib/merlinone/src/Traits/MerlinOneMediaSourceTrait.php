<?php

namespace Drupal\merlinone\Traits;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;

/**
 * A MerlinOne Media Source.
 */
trait MerlinOneMediaSourceTrait {

  use StringTranslationTrait;

  /**
   * Gets a list of metadata attributes provided by Merlin.
   *
   * @return array
   *   Associative array with:
   *   - keys: metadata attribute names
   *   - values: human-readable labels for those attribute names
   */
  protected function getMerlinMetadataAttributes() {
    return [
      'headline' => $this->t('Headline'),
      'caption' => $this->t('Caption'),
      'copyright' => $this->t('Copyright'),
      'merlin_id' => $this->t('Merlin ID'),
      'keywords' => $this->t('Keywords'),
    ];
  }

  /**
   * Handle Merlin metadata attribute for a given media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   * @param string $attribute_name
   *   Name of the attribute to fetch.
   *
   * @return mixed|null
   *   Metadata attribute value or NULL if unavailable.
   */
  protected function getMerlinMetadata(MediaInterface $media, $attribute_name) {
    $field_map = $media->bundle->entity->getFieldMap();

    // $original_item holds the response from Merlin after entity creation,
    // so pull data from there if it exists, otherwise get the values from the
    // media entity.
    if (isset($media->original_item) && $original_item = $media->original_item) {
      switch ($attribute_name) {
        case 'merlin_id':
          return isset($original_item->cimageid) ? $original_item->cimageid : FALSE;

        case 'caption':
          return isset($original_item->capt2120) ? $original_item->capt2120 : FALSE;

        case 'keywords':
          if (!empty($original_item->keywords)) {
            /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
            $term_storage = $this->getEntityTypeManager()->getStorage('taxonomy_term');
            $keywords = [];

            $handler_settings = $media->getFieldDefinition($field_map[$attribute_name])->getSetting('handler_settings');
            $keywords_vocabulary = count($handler_settings['target_bundles']) > 1 ? $handler_settings['auto_create_bundle'] : reset($handler_settings['target_bundles']);

            foreach (explode(',', $original_item->keywords) as $keyword) {
              $keyword = trim($keyword);
              $term_candidates = $term_storage->loadByProperties([
                'vid' => $keywords_vocabulary,
                'name'  => $keyword,
              ]);

              if (empty($term_candidates)) {
                $term = $term_storage->create([
                  'vid' => $keywords_vocabulary,
                  'name' => $keyword,
                ]);
                $term->save();
              }
              else {
                $term = array_shift($term_candidates);
              }

              $keywords[] = $term->id();
            }

            return $keywords;
          }
          break;

        default:
          return isset($original_item->{$attribute_name}) ? $original_item->{$attribute_name} : FALSE;
      }
    }
    elseif (isset($media->{$field_map[$attribute_name]})) {
      return $media->{$field_map[$attribute_name]};
    }

    return FALSE;
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  abstract protected function getEntityTypeManager();

}
