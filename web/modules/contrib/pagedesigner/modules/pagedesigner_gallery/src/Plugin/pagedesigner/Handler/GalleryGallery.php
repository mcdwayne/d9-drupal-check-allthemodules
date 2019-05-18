<?php
namespace Drupal\pagedesigner_gallery\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;

/**
 * @PagedesignerHandler(
 *   id = "gallery_component",
 *   name = @Translation("Gallery component handler"),
 *   types = {
 *      "gallery_gallery"
 *   },
 * )
 */
class GalleryGallery extends Standard
{

    /**
     * {@inheritDoc}
     */
    public function serialize(Element $entity)
    {
        $data = [];
        if ($entity->children) {
            foreach ($entity->children as $item) {
                if ($item->entity->field_media->entity != null && $item->entity->field_media->entity->field_media_image->entity != null) {
                    $file = $item->entity->field_media->entity->field_media_image->entity;
                    $url = $previewUrl = $file->url();
                    // $style = \Drupal::entityTypeManager()
                    //     ->getStorage('image_style')
                    //     ->load('ali_placeholder');
                    // if ($style != null) {
                    //     $url = $style->buildUrl($file->getFileUri());
                    // }
                    // $style = \Drupal::entityTypeManager()
                    //     ->getStorage('image_style')
                    //     ->load('default');
                    // if ($style != null) {
                    //     $url = $previewUrl = $style->buildUrl($file->getFileUri());
                    // }
                    // $style = \Drupal::entityTypeManager()
                    //     ->getStorage('image_style')
                    //     ->load('large');
                    // if ($style != null) {
                    //     $previewUrl = $style->buildUrl($file->getFileUri());
                    // }

                    $data[] = [
                        'alt' => $item->entity->field_content->value,
                        'id' => $item->entity->field_media->target_id,
                        'src' => $url,
                        'preview' => $previewUrl,
                    ];
                }

            }
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        // Defining build array
        $build = [];
        $build['type'] = 'gallery_gallery';
        if (isset($data['items'])) {
            $newItems = [];
            $oldChildren = $entity->children;
            foreach ($entity->children as $item) {
                $item->entity->delete();
            }
            $entity->children->setValue(array());
            foreach ($data['items'] as $entry) {
                $child = Element::create(['type' => 'gallery_item', 'name' => 'gallery_item']);
                $child->field_media->target_id = $entry['id'];
                $child->field_content->value = $entry['alt'];
                $child->parent->entity = $entity;
                $child->save();
                $entity->children->appendItem($child);
                $build['items'][] = $child->field_media->target_id;
            }
            $entity->saveEdit();
        }
        return $build;
    }
}
