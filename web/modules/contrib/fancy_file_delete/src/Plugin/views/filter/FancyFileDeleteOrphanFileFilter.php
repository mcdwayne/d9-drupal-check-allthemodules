<?php

namespace Drupal\fancy_file_delete\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Fancy File Delete Orphan Files Views Settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ffd_orphan_filter")
 */

class FancyFileDeleteOrphanFileFilter extends FilterPluginBase {

  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Hide default behavoir just in case.
    $form['expose_button']['#access'] = FALSE;
    $form['more']['#access'] = FALSE;

    $form['orphan_text'] = array(
      '#type' => 'item',
      '#markup' => t('This is just a custom query filter no need for any configuration.')
    );
  }

  public function query() {
    $table = $this->ensureMyTable();

    $query = "SELECT fm.* FROM file_managed AS fm LEFT OUTER JOIN file_usage
    AS fu ON (fm.fid = fu.fid) LEFT OUTER JOIN node AS n ON (fu.id = n.nid)
    WHERE fu.type = 'node' AND n.nid IS NULL";

    $results = db_query($query)->fetchAll();

    if (count($results) > 0) {
      foreach ($results as $result) {
        $files[] = $result->fid;
      }
      $this->query->addWhere($this->options['group'], $table . '.fid',  $files, 'IN');
    }
    else {
      // No Results, return NULL, carry on.
      $this->query->addWhere($this->options['group'], $table . '.fid',  NULL, '=');
    }
  }
}
