<?php /**
 * @file
 * Contains \Drupal\google_adwords_path\Controller\DefaultController.
 */

namespace Drupal\google_adwords_path\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the google_adwords_path module.
 */
class DefaultController extends ControllerBase {


  public function google_adwords_path_admin_page() {
    $codes = google_adwords_path_get_all_codes();

    $header = [
      t('No'),
      t('Name'),
      t('Conversion ID'),
      t('Language'),
      t('Format'),
      t('Color'),
      t('Label'),
      t('Operations'),
    ];
    $rows = [];
    $count = 1;
    foreach ($codes as $code) {
      // @FIXME
      // $rows[] = array(
      //       array('data' => $count),
      //       array('data' => $code['name']),
      //       array('data' => $code['conversion_id']),
      //       array('data' => $code['conversion_language']),
      //       array('data' => $code['conversion_format']),
      //       array('data' => $code['conversion_color']),
      //       array('data' => $code['conversion_label']),
      //       array('data' => l(t('edit'), 'admin/config/system/google_adwords/path/' . $code['cid'] . '/edit') . ' ' . l(t('delete'), 'admin/config/system/google_adwords/path/' . $code['cid'] . '/delete')),
      //     );

      $count++;
    }

    if ($rows) {
      return _theme('table', ['header' => $header, 'rows' => $rows]);
    }
    else {
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // return '<p>' . t('No conversion code yet. !add_link?', array('!add_link' => l(t('Add one'), 'admin/config/system/google_adwords/path/add'))) . '</p>';

    }
  }
}
