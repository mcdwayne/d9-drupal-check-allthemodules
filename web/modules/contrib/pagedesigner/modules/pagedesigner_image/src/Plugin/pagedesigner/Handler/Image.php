<?php
namespace Drupal\pagedesigner_image\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "image",
 *   name = @Translation("Image handler"),
 *   types = {
 *      "image",
 *      "img",
 *   },
 * )
 */
class Image extends Standard
{

    public function collectAttachments(&$attachments)
    {
        $attachments['library'][] = 'pagedesigner_image/pagedesigner';
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(PatternDefinitionField &$field, &$fieldArray)
    {
        parent::prepare($field, $fieldArray);
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Element $entity)
    {
        $data = [
            'src' => '',
            'id' => $entity->field_media->target_id,
        ];
        if ($entity->field_media->entity != null) {
            $data['src'] = $entity->field_media->entity->field_media_image->entity->url();
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        if ($entity->field_media->entity != null && $entity->field_media->entity->field_media_image->entity != null) {
            $file = $entity->field_media->entity->field_media_image->entity;
            $style = \Drupal::entityTypeManager()
                ->getStorage('image_style')
                ->load('ali_placeholder');
            if ($style != null) {
                return $style->buildUrl($file->getFileUri());
            }
            $style = \Drupal::entityTypeManager()
                ->getStorage('image_style')
                ->load('pagedesigner_default');
            if ($style != null) {
                return $style->buildUrl($file->getFileUri());
            }

            return $file->url();
        }
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        if (!empty($data['id'])) {
            $entity->field_media->target_id = $data['id'];
            $entity->saveEdit();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        return parent::generate(['type' => 'image', 'name' => 'image'], $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Element $entity)
    {
        parent::delete($entity);
    }
}
