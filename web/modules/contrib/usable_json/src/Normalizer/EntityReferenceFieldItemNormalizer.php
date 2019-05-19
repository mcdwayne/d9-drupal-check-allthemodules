<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\Entity\File;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceFieldItemNormalizer extends ComplexDataNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;
  private static $allowedEntityTypeResolving = array('paragraph', 'media');

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $field_item->get('entity')->getValue()) {
      if (in_array($entity->getEntityTypeId(), self::$allowedEntityTypeResolving)) {
        $normalize = \Drupal::service("serializer");
        $values = $normalize->normalize($entity, $format, $context);
        switch ($entity->getEntityTypeId()) {
          case 'media':
            $this->cleanUpMediaEntity($values, $entity->bundle(), $entity);
            break;

          case 'paragraph':
            $this->cleanUpParagraphEntity($values, $entity->bundle());
            break;
        }
      }
    }
    return $values;
  }

  /**
   * Clean up media entity.
   *
   * @param array $values
   *   Field values.
   * @param string $bundle
   *   Field bundle.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   */
  public function cleanUpMediaEntity(array &$values, $bundle, EntityInterface $entity) {
    if (\Drupal::moduleHandler()->moduleExists('media')) {
      /* @var \Drupal\media\Entity\Media $entity */
      $media_source = $entity->getSource();
    }
    else {
      /* @var \Drupal\media_entity\Entity\Media $entity */
      $media_source = $entity->getType();
    }
    $source_field = $media_source->getConfiguration()['source_field'];

    switch ($media_source->getPluginId()) {
      case 'image':
        $values = $values[$source_field][0];
        $values['type'] = $bundle;
        break;

      case 'video':
        $fid = $values[$source_field][0]['target_id'];
        $file = File::load($fid);
        $values = [
          'type' => $bundle,
          'sources' => [
            'src' => file_create_url($file->getFileUri()),
            'type' => $file->getMimeType(),
          ],
          'video' => file_create_url($file->getFileUri()),
        ];
        break;

      case 'video_embed':
        $providerManager = \Drupal::service('video_embed_field.provider_manager');
        $providerInfo = $providerManager->loadDefinitionFromInput($entity->{$source_field}->value);
        $videoID = $providerInfo['class']::getIdFromInput($entity->{$source_field}->value);

        $values = [
          'type' => $bundle,
          'video_provider' => $providerInfo['id'],
          'video_id' => $videoID,
        ];
        break;

      default:
        $values['type'] = $entity->getType()->getBaseId();
    }

  }

  /**
   * Clean up paragraph entity.
   *
   * @param array $values
   *   Field values.
   * @param string $bundle
   *   Field bundle.
   */
  public function cleanUpParagraphEntity(array &$values, $bundle) {
    unset($values['revision_id']);
    unset($values['id']);
    unset($values['uid']);
    unset($values['status']);
    unset($values['created']);
    unset($values['revision_uid']);
    unset($values['parent_id']);
    unset($values['parent_type']);
    unset($values['parent_field_name']);
    unset($values['behavior_settings']);
    unset($values['default_langcode']);
    unset($values['revision_translation_affected']);
    unset($values['moderation_state']);
    unset($values['metatag']);
  }

}
