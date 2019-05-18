<?php
namespace Drupal\pagedesigner_embed\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "embed",
 *   name = @Translation("Embed handler"),
 *   types = {
 *      "embed"
 *   },
 * )
 */
class Embed extends Standard
{

    public function collectAttachments(&$attachments)
    {
        $attachments['library'][] = 'pagedesigner_embed/pagedesigner';
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
            $data['src'] = $entity->field_media->entity->field_media_oembed_video->value;
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        if ($entity->field_media->entity != null) {
            $entity->field_media->entity->field_media_oembed_video->value;
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
        return parent::generate(['type' => 'embed', 'name' => 'embed'], $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Element $entity)
    {
        parent::delete($entity);
    }
}
