<?php

/**
 * @file
 * Contains \Drupal\feedmine\Controller\FeedmineAdminController.
 */

namespace Drupal\feedmine\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for building the block instance add form.
 */
class FeedmineAdminController extends ControllerBase {
  public function FeedmineAdminOverview() {
    $output = t("Complete the following steps to configure Feedmine:<br/>");
    $output .= t("</br><b>Redmine:</b>");
    $output .= t("<UL>");
    $output .= t("<li>Confirm your version of Redmine is newer than <code>1.1.0</code> by visiting <code>/admin/info</code></li>");
    $output .= t("<li>Check <code>Enable REST API</code> by going to <code>Administration -> Settings -> Authentication</code></li>");
    $output .= t("<li>Copy your API access key located on your Redmine account page at <code>/my/account</code>.  While logged into Redmine, it's on the right-hand pane of the default layout.  Click <code>Show</code> under <code>API access key</code>.</li>");
    $output .= t('</UL>');
    $output .= t('<br><b>Feedmine:</b>');
    $output .= t('<UL>');
    $output .= t('<li>Define <a href="/admin/config/feedmine/feedmine_settings/rmapi"> Redmine API settings </a> .</li>');
    $output .= t('<li>Select a <a href="/admin/config/feedmine/feedmine_settings/rmproject">Redmine project</a> to post feedback issues to.</li>');
    $output .= t('<li>Define <a href="/admin/people/permissions">Feedmine module permissions</a>.</li>');


    $element = array(
      '#markup' => $output,
    );
    return $element;
  }
}