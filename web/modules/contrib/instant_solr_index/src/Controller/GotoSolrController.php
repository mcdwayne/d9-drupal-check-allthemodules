<?php

namespace Drupal\instant_solr_index\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api\Entity\Server;
use Drupal\Core\Url;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Component\Utility\Html;

class GotoSolrController extends ControllerBase {

 /**
  * Page callback for Home Page tab.
  */
  public function instant_solr_index_intro() {
    return array(
      '#theme' => 'gotosolr_homepage',
    );
  }
  
  /**
   * Page callback for Search Api Index tab.
   */
  public function instant_solr_index_searchapi_config() {
    $server_type = 'searchApi';
    if (!function_exists('search_api_help')) {
      drupal_set_message(t('Please install Search API solr module.'), 'error');
      return array('#type' => 'markup', '#markup' => '');
    }
    $query_params = \Drupal::request()->query->get('query');
    if (isset($query_params["servercreated"]) && !empty($query_params['servercreated'])) {
      if ($query_params['servercreated'] == 'success') {
        drupal_set_message(t('Server has been created.'));
      }
    }
    if (isset($query_params['gotosolrCustomErrorMsg'])) {
      $custom_error_msg = $query_params['gotosolrCustomErrorMsg'];
      drupal_set_message(Html::escape($custom_error_msg), 'error');
    }
    $servers = Server::loadMultiple();
    $gotosolr_server_id = \Drupal::config('instant_solr_index.settings')->get('instant_solr_index_searchApiServer_id');
    $rows = array();
    $gotosolr_server_exist = FALSE;
    $managed_solr_server = new InstantSolrIndexOptionManagedSolrServer('gotosolr', $server_type);
    foreach ($servers as $server) {
      $row_class = '';
      if ($server->id() == $gotosolr_server_id) {
        $response_object = $managed_solr_server->callRestGetTemporarySolrIndexStatus($server->getBackendConfig()['urlCore']);
        if ($response_object->results[0][0]->isUnknown == TRUE) {
          $row_class = 'error_row';
        }
        $append_order_link = self::instant_solr_index_extend_server_links($managed_solr_server, $server->getBackendConfig()['urlCore'], $response_object);
        $edit_link = \Drupal::l(t("Edit"), Url::fromRoute('instant_solr_index.edit-search-api-server'));
        $purchase_link1 = $append_order_link[0];
        $purchase_link2 = $append_order_link[1];
        $gotosolr_server_exist = TRUE;
      }
      else {
        $edit_link = \Drupal::l(t("Edit"), Url::fromRoute('entity.search_api_server.edit_form', array('search_api_server' => $server->id()), array('query' => array('destination' => 'admin/config/search/gotosolr/searchapi'))));
        $purchase_link1 = '';
        $purchase_link2 = '';
      }
      $rows[] = array(
        'data' => array(Html::escape($server->label()), $edit_link, $purchase_link1, $purchase_link2),
        'class' => array($row_class),
      );
    }
    $servers_list = array('#type' => 'table', '#header' => array(), '#rows' => $rows);
    $html = '<div><i>' . t('Configure Solr Server.') . '</i></div>';
    $html .= '<div>' . t('The page lists your existing search environments/servers.') . '</div><br>';
    $add_search_environment = '<span>' . \Drupal::l(t("Add Search Environment"), Url::fromRoute('search_api.overview')) . '</span>&nbsp;';
    if ($gotosolr_server_exist == FALSE) {
      $url = Url::fromRoute('instant_solr_index.introduction');
      $link_options = array(
        'attributes' => array(
          'class' => array(
            'add-search-env',
          ),
        ),
      );
      $url->setOptions($link_options);
      $api_solr_link = \Drupal::l(t("Create a free test Solr index hosted by gotosolr.com (valid for 2 hours)"), $url);
    }
    else {
      $api_solr_link = '';
    }

    $response_object = $managed_solr_server->callRestCreateGoogleRecaptchaToken();
    if (isset($response_object) && InstantSolrIndexOptionManagedSolrServer::isResponseOk($response_object)) {
      $google_recaptcha_site_key = InstantSolrIndexOptionManagedSolrServer::getResponseResult($response_object, 'siteKey');
      $google_recaptcha_token = InstantSolrIndexOptionManagedSolrServer::getResponseResult($response_object, 'token');
    }
    else {
      return $this->redirect('instant_solr_index.searchapi', array('query' => array('gotosolrCustomErrorMsg' => $response_object->status->message)));
    }
    $captcha_form = \Drupal::formBuilder()->getForm('Drupal\instant_solr_index\Form\CaptchaForm', $google_recaptcha_site_key, $google_recaptcha_token, $server_type);
    $captcha_form['#prefix'] = $html . $add_search_environment . ' ' . $api_solr_link;
    $captcha_form['#suffix'] = drupal_render($servers_list);
    $captcha_form['#attached']['library'][] = 'instant_solr_index/instant_solr_index-library';
    return $captcha_form;
  }

  /**
   * Links to buy Server.
   */
  public function instant_solr_index_extend_server_links($managed_solr_server, $url_core, $response_object) {
    if ($response_object->results[0][0]->isUnknown == TRUE) {
      drupal_set_message(t('Your test index has expired and is no longer valid. Please remove it, and eventually create a new one.'), 'warning');
    }
    $append_order_link = [];
    if ($response_object->results[0][0]->isTemporary == TRUE) {
      $order_urls = $managed_solr_server->generateConvertOrdersUrls($url_core);
      $order_urls = array_reverse($order_urls, TRUE);
      foreach ($order_urls as $order_url) {
        $append_order_link[] = \Drupal::l($order_url['MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL'], Url::fromUri($order_url['MANAGED_SOLR_SERVICE_ORDER_URL_LINK'], array('attributes' => array('target' => '_blank'))));
      }
    }
    return $append_order_link;
  }
  
  /**
   * Edit searchapi server on drupal.
   */
  public function instant_solr_index_searchapi_edit() {
    $server_type = 'searchApi';
    $managed_solr_server = new InstantSolrIndexOptionManagedSolrServer('gotosolr', $server_type);
    $gotosolr_server_id = \Drupal::config('instant_solr_index.settings')->get('instant_solr_index_searchApiServer_id');
    $server = Server::load($gotosolr_server_id);
    $response_object = $managed_solr_server->callRestGetTemporarySolrIndexStatus($server->getBackendConfig()['urlCore']);
    if ($response_object->results[0][0]->isUnknown == TRUE) {
      $index_expired = TRUE;
    }
    else {
      $index_expired = FALSE;
    }
    $server_details = array(
      'name' => $server->label(),
      'protocol' => $server->getBackendConfig()['scheme'],
      'host' => $server->getBackendConfig()['host'],
      'port' => $server->getBackendConfig()['port'],
      'path' => $server->getBackendConfig()['path'],
      'key' => $server->getBackendConfig()['key'],
      'secret' => $server->getBackendConfig()['secret'],
      'purchase_server' => self::instant_solr_index_extend_server_links_on_edit($managed_solr_server, $server->getBackendConfig()['urlCore']),
      'submit_function' => 'instant_solr_index_searchapiserver_edit_submit',
      'delete_function' => 'instant_solr_index_searchapiserver_edit_delete',
    );
    $server_edit_form = \Drupal::formBuilder()->getForm('Drupal\instant_solr_index\Form\ServerEditForm', $server_details, $response_object, $index_expired);
    return $server_edit_form;
  }

  /**
   * Buy server links on edit page.
   */
  public function instant_solr_index_extend_server_links_on_edit($managed_solr_server, $url_core) {
    $response_object = $managed_solr_server->callRestGetTemporarySolrIndexStatus($url_core);

    $append_order_link = '';
    if ($response_object->results[0][0]->isTemporary == TRUE) {
      $order_urls = $managed_solr_server->generateConvertOrdersUrls($url_core);
      $order_urls = array_reverse($order_urls, TRUE);
      $append_order_link = '';
      foreach ($order_urls as $order_url) {
        $append_order_link .= '<span>' . \Drupal::l($order_url['MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL'], Url::fromUri($order_url['MANAGED_SOLR_SERVICE_ORDER_URL_LINK'], array('attributes' => array('target' => '_blank')))) . '</span>&nbsp;';
      }
    }
    return $append_order_link . '<br><br>';
  }

}
