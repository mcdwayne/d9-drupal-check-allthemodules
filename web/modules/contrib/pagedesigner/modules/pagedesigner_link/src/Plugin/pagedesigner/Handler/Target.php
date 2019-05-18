<?php
namespace Drupal\pagedesigner_link\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;

/**
 * @PagedesignerHandler(
 *   id = "target",
 *   name = @Translation("Target handler"),
 *   types = {
 *      "target",
 *   },
 * )
 */
class Target extends Standard
{

    public function collectAttachments(&$attachments)
    {
        $attachments['library'][] = 'pagedesigner_link/pagedesigner';
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        return $entity->field_content->value;
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        $element = parent::generate(['type' => 'content'], $data);
        return $element;
    }
}
