<?php
namespace Drupal\pagedesigner_gallery\Plugin\pagedesigner\Handler;

use Drupal\Core\Language\LanguageInterface;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "gallery",
 *   name = @Translation("gallery handler"),
 *   types = {
 *      "gallery",
 *   },
 * )
 */
class Gallery extends Standard
{

    public function collectAttachments(&$attachments)
    {
        $galleryIds = \Drupal::entityQuery('pagedesigner_element')->condition('type', 'gallery_gallery')->condition('deleted', null, 'IS NULL')->execute();
        $galleries = [];
        foreach ($galleryIds as $id) {
            $gallery = Element::load($id);
            $galleries[$id] = $gallery->name->value;
        }
        $attachments['drupalSettings']['pagedesigner_gallery']['galleries'] = $galleries;
        $attachments['library'][] = 'pagedesigner_gallery/pagedesigner';
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
        $data = [];

        if ($entity->field_gallery->entity != null) {
            $handler = $this->handlerManager->getInstance(['type' => $entity->field_gallery->entity->bundle()])[0];
            $data = [
                'id' => $entity->field_gallery->target_id,
                'name' => $entity->field_gallery->entity->name->value,
                'items' => $handler->serialize($entity->field_gallery->entity),
            ];
        }
        return $data;

    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        if ($entity->field_gallery->entity != null) {
            $handler = $this->handlerManager->getInstance(['type' => $entity->field_gallery->entity->bundle()])[0];
            return $handler->serialize($entity->field_gallery->entity);
        } else {
            return [];
        }

    }

    /**
     * {@inheritdoc}
     */
    public function render(Element $entity)
    {
        return $this->get($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        $type = $definition['type'];
        $name = (!empty($definition['name'])) ? $definition['name'] : $definition['type'];
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
        $element = Element::create(['type' => $type, 'name' => $name, 'langcode' => $language]);
        $element->parent->target_id = $data['parent'];
        $element->container->target_id = $data['container'];
        $element->field_placeholder->value = $data['placeholder'];
        $element->saveEdit();
        return $element;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        // Defining build array
        $build = ['type' => 'gallery', 'gallery' => ['id' => null, 'name' => '', 'items' => []]];
        if (\is_numeric($data['id'])) {
            $entity->field_gallery->target_id = $data['id'];
            $entity->field_gallery->entity->name->value = $data['name'];
            $entity->field_gallery->entity->save();
            $entity->saveEdit();
            $build['gallery']['id'] = $entity->field_gallery->entity->id();
            $build['gallery']['name'] = $entity->field_gallery->name->value;
        } elseif ($data['id'] == null) {
            $entity->field_gallery->entity = Element::create(['type' => 'gallery_gallery', 'name' => 'Gallery ']);
            $entity->field_gallery->entity->save();
            if (empty($data['name'])) {
                $entity->field_gallery->entity->name->value .= $entity->field_gallery->entity->id();
            } else {
                $entity->field_gallery->entity->name->value = $data['name'];
            }
            $entity->field_gallery->entity->save();
            $entity->saveEdit();
            $build['gallery']['id'] = $entity->field_gallery->entity->id();
            $build['gallery']['name'] = $entity->field_gallery->entity->name->value;
        }
        if ($entity->field_gallery->entity != null) {
            if (isset($data['items'])) {
                $handler = $this->handlerManager->getInstance(['type' => $entity->field_gallery->entity->bundle()])[0];
                $build['gallery']['items'] = $handler->patch($entity->field_gallery->entity, $data);
            }
        }
        return $build;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Element $entity)
    {
        parent::delete($entity);
    }
}
