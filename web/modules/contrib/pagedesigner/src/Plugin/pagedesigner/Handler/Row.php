<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;

/**
 * @PagedesignerHandler(
 *   id = "row",
 *   name = @Translation("Row render"),
 *   types = {
 *     "row",
 *   },
 * )
 */
class Row extends Structural
{
    /**
     * {@inheritDoc}
     */
    public function adaptPatterns(&$patterns)
    {
        if ($_SERVER['REMOTE_ADDR'] == '83.150.28.13') {
            foreach ($patterns as $id => $pattern) {
                if ($pattern['type'] == 'row') {
                    $dom = new \DOMDocument;
                    libxml_use_internal_errors(true);
                    $dom->loadHTML(mb_convert_encoding($pattern['markup'], 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING);
                    libxml_clear_errors();
                    $root = $dom->documentElement;
                    $columns = [];
                    $children = $root->childNodes;
                    foreach ($children as $item) {
                        if ($item->nodeName != '#text' && strpos($item->getAttribute('class'), 'iq-column') !== false) {
                            $item->setAttribute('data-gjs-type', 'cell');
                        }
                    }
                    $patterns[$id]['markup'] = $dom->saveHTML();
                }
            }
        }
    }

    public function patch(Element $entity, $data)
    {
        $build = parent::patch($entity, $data);
        $build['type'] = 'row';
        return $build;
    }

    public function serialize(Element $entity)
    {
        $build = parent::serialize($entity);
        $build['type'] = 'row';
        return $build;
    }
}
