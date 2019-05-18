<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;

/**
 * @PagedesignerHandler(
 *   id = "content",
 *   name = @Translation("Content renderer"),
 *   types = {
 *     "content",
 *     "checkbox",
 *     "fontawesome",
 *     "toggle"
 *   },
 * )
 */
class Content extends Standard
{
    public function generate($definition, $data)
    {
        return parent::generate(['type' => 'content', 'name' => $definition['type']], $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(Element $entity)
    {
        return [
            '#type' => 'inline_template',
            '#template' => '{{markup}}',
            '#context' => ['markup' => ['#markup' => $this->get($entity)]],
        ];
    }
}
