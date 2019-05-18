<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/2/2016
 * Time: 8:20 AM
 */

namespace Drupal\forena\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\forena\DataManager;
use Drupal\forena\Form\DataSourceDefinitionForm;
use Symfony\Component\HttpFoundation\Request;

class DataSourceController extends ControllerBase {

  /**
   * List the data sources in use by Forena.
   */
  public function listDataSources() {
    $repos = DataManager::instance()->repositories;
    $r_list = array();
    $headers = array(t('Name'), t('Description'), t('source'), t('Operation'));
    $sources = [
      '#type' => 'table',
      '#rows' => [],
    ];
    foreach ($repos as $name => $r) {
      $title = isset($r['title']) ? $r['title'] : $name;
      $sources['#rows'][] =[
        $name,
        $title,
        $r['source'],
        // $link to configure
        Link::createFromRoute($this->t('edit'), 'forena.configure.datasource', ['source' => $name]),
      ];
    }

    // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
    // $output = '<ul class="action-links"><li>' . l(t('Add data source'), 'admin/config/content/forena/data/add') . '</li></ul>';

    if ($sources['#rows']) $content['data_sources'] = $sources;
    return $content;
  }

}