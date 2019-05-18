<?php

namespace Drupal\ajax_links_api\Plugin;

/**
 * Ajax Links ApiService Class.
 */
class AjaxLinksApiService {

  /**
   * Ajax links API.
   *
   * @param string $ajax_link_title
   *   Title to display.
   * @param string $ajax_link_path
   *   Drupal path eg: user/login.
   * @param string $ajax_link_target
   *   ID or CLASS of DIV to be replaced. eg: #content-content or #content.
   * @param array $ajax_link_options
   *   Array of link options eg: array(
   *    '#attributes' => 'class' => array(
   *      'ajax-links-api'
   *    ))
   *
   * @return string
   *   a link with class ajax_link and rel=$ajax_link_target.
   */
  public function lAjax($ajax_link_title, $ajax_link_path, $ajax_link_target, $ajax_link_options = array()) {
    $url = \Drupal::service('path.validator')->getUrlIfValid($ajax_link_path);

    $ajax_link_options['attributes']['class'][] = 'ajax-link';
    $ajax_link_options['attributes']['rel'] = $ajax_link_target;

    $url->setOptions($ajax_link_options);
    $ajax_link = \Drupal::l($ajax_link_title, $url);

    return $ajax_link;
  }

}
