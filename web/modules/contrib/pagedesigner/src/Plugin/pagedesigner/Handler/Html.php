<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\Core\Render\Markup;
use Drupal\editor\Entity\Editor;
use Drupal\pagedesigner\Entity\Element;

/**
 * @PagedesignerHandler(
 *   id = "html",
 *   name = @Translation("Html renderer"),
 *   types = {
 *     "html"
 *   },
 * )
 */
class Html extends Content
{

    public function serialize(Element $entity)
    {
        return $entity->field_content->value;
    }

    /**
     * {@inheritdoc}
     */
    public function get(Element $entity)
    {
        $formatId = Editor::load('pagedesigner')->getFilterFormat()->id();
        $build = [
            '#type' => 'processed_text',
            '#text' => $entity->field_content->value,
            '#format' => $formatId,
            '#filter_types_to_skip' => [],
            '#langcode' => $entity->langcode->value,
        ];
        return \Drupal::service('renderer')
            ->renderPlain($build);
    }
}
