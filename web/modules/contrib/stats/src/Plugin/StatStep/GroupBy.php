<?php
/**
 * @file
 * GroupBy.php for kartslalom
 */

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;
use Drupal\stats\RowCollection;

/**
 * @StatStep(
 *   id = "group_by",
 *   label = "Group By"
 * )
 */
class GroupBy extends StatStepBase {

  /**
   * {@inheritdoc}
   */
  public function process(RowCollection $collection) {
    // First we collect the new values so we build a new
    $groups = [];
    foreach ($collection as $row) {
      $g = $this->getGroupBy($row);
      $hash = md5(serialize($g));
      if (!isset($groups[$hash])) {
        $groups[$hash] = [
          'g' => $g,
          'rows' => [],
        ];
      }
      $groups[$hash]['rows'][] = $row;
    }

    $collection->empty();
    foreach ($groups as $group) {
      $new_row = new Row([]);

      // First we set the fields that we grouped by.
      foreach ($group['g'] as $prop => $val) {
        $new_row->setProperty($prop, $val);
      }

      if (!empty($this->configuration['properties'])) {
        // Then we collect the properties defined.
        foreach ($this->configuration['properties'] as $prop => $source) {
          $vals = [];
          /** @var Row $row */
          foreach ($group['rows'] as $row) {
            $vals[] = $row->getProperty($source);
          }
          $new_row->setProperty($prop, $vals);
        }
      }
      $collection->addRow($new_row);
    }
  }

  /**
   * @param \Drupal\stats\Row $row
   *
   * @return array
   */
  protected function getGroupBy(Row $row) {
    $g = [];
    foreach ($this->configuration['group_by'] as $group_by_selector) {
      $g[$group_by_selector] = $row->getProperty($group_by_selector);
    }
    return $g;
  }

}
