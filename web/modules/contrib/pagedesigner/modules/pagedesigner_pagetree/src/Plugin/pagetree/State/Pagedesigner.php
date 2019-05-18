<?php
namespace Drupal\pagedesigner_pagetree\Plugin\pagetree\State;

use Drupal\pagetree\Plugin\pagetree\State\Standard;

/**
 * @StateHandler(
 *   id = "pagedesigner",
 *   name = @Translation("pagedesigner render"),
 *   weight = 100
 * )
 */
class Pagedesigner extends Standard
{
    public function annotate(&$entries)
    {
        $ids = [];
        foreach ($entries as $entry) {
            $ids[$entry['langcode']][] = $entry['nid'];
        }
        foreach ($ids as $langcode => $nodes) {
            $stmt = \Drupal::database()->query(
                "SELECT container, status FROM pagedesigner_element_field_data WHERE `container` IN (:nodes[]) AND langcode LIKE :langcode AND status = 0 AND COALESCE(deleted, 0) = 0 GROUP BY container, status",
                [
                    ':nodes[]' => $nodes,
                    ':langcode' => $langcode,
                ]
            );
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($results as $row) {
              $entries[$row->container . $langcode]['status'] = 0;
            }
        }
    }
}
