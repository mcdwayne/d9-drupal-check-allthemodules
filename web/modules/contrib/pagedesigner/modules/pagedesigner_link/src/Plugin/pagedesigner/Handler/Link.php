<?php
namespace Drupal\pagedesigner_link\Plugin\pagedesigner\Handler;

use Drupal\Core\Url;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "link",
 *   name = @Translation("Link handler"),
 *   types = {
 *      "link",
 *      "href",
 *   },
 * )
 */
class Link extends Standard
{

    public function collectAttachments(&$attachments)
    {
        // $attachments['drupalSettings']['pagedesigner']['link']['profile'] = 'pagedesigner';
        $attachments['library'][] = 'pagedesigner_link/pagedesigner';
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
            'uri' => $entity->field_link->uri,
            'title' => $entity->field_link->title,
        ];
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        $uri = $entity->field_link->uri;
        if (strpos($entity->field_link->uri, '/') === 0 && strpos($entity->field_link->uri, 'media') !== false) {
            $params = Url::fromUri("internal:" . $entity->field_link->uri)->getRouteParameters();
            $entity_type = key($params);
            if ($entity_type != null && !empty($entity_type)) {
                $targetEntity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
                $source = $targetEntity->getSource();
                $config = $source->getConfiguration();
                $field = $config['source_field'];
                $file = $targetEntity->{$field}->entity;
                if ($file == null) {
                    $uri = $entity->toUrl('canonical')->toString(true);
                }
                $uri = file_create_url($file->getFileUri());
                if (empty($uri)) {
                    $uri = $entity->toUrl('canonical')->toString(true);
                }
            }
        }
        if (empty($uri)) {
            $uri = '#';
        }
        return $uri;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        if (!empty($data['uri'])) {
            $entity->field_link->uri = $data['uri'];
            $entity->field_link->title = $data['title'];
            $entity->saveEdit();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        return parent::generate(['type' => 'link', 'name' => 'link'], $data);
    }
}
