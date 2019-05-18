<?php

namespace Drupal\fancy_file_delete\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the fancy_file_delete module.
 */
class DefaultController extends ControllerBase {

  public function fancy_file_delete_info() {
    $html = '<h1>' . t('The D8 verison of this module still needs some loving, see issue ') . '<a href="https://www.drupal.org/node/2579961" target="_blank">' . t('HERE') . '</a></h1>';
    $html .= '<h2>' . t('Fancy File Delete Options') . '</h2>';
    $html .= '<ol>';
    $html .= '<li>' . t('<b>LIST:</b> View of all managed files with an <em>option to force</em> delete them via VBO custom actions') . '</li>';
    $html .= '<li>' . t('<b>MANUAL:</b> Manually deleting managed files by FID (and an  <em>option to force</em> the delete if you really want to).') . '</li>';
    $html .= '<li>' . t('<b>ORPHANED:</b> Deleting unused files from the whole install that are no longer attached to nodes & the file usage table. AKA deleting all the orphaned files.') . '</li>';
    $html .= '<li>' . t('<b>UNMANAGED:</b> Deleting unused files from the default files directory that are not in the file managed table. AKA deleting all the unmanaged files.') . '</li>';
    $html .= '</ol>';

    return ['#markup' => $html];
  }

}
