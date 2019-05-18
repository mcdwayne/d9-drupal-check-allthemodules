<?php

namespace Drupal\multisite_solr_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Zend\Diactoros\Response\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchResultsController.
 */
class SearchResultsController extends ControllerBase {

  /**
   * Searchresults.
   *
   * @return string
   *   Return Hello string.
   */
  public function searchResults(Request $request) {
    // Get the selected server
    $server = \Drupal::config('multisite_solr_search.selectserver')->get('select_server');
    $keyword = $request->query->get('q');
    global $base_url;
    // Get server configurations.
    $server_name = 'search_api.server.' . $server;
    $config = \Drupal::config($server_name);
    $backend_config = $config->get('backend_config');
    $solrConnector = \Drupal::service('plugin.manager.search_api_solr.connector');
    $connector_config = $backend_config['connector_config'];
    // Connect to solr server.
    $connector = $solrConnector->createInstance('standard', $connector_config);
    $query = new \Solarium\QueryType\Select\Query\Query();
    $query->setQueryDefaultField("ts_title");
    $query->getHighlighting();
    // Add highlighting parameter to query.
    $query->addParam('hl', 'on');
    $query->addParam('hl.fl', 'ts_title');
    $user_input = Html::escape($keyword);
    $words = explode(' ', $user_input);
    // Keyword search.
    if ($user_input) {
      $user_input = htmlspecialchars_decode($user_input, ENT_QUOTES);
      $helper = $query->getHelper();

      // Phrase search.
      $placeholder_count = 1;
      $placeholders[] = '%P' . $placeholder_count . '%';
      $contents[] = $user_input;

      // Word search.
      if (count($words) > 1) {
        foreach ($words as $key => $word) {
          if (strlen($word) < 3) {
            unset($words[$key]);
          }
          else {
            $placeholder_count++;
            $placeholders[] = '%L' . $placeholder_count . '%';
            $contents[] = $helper->escapeTerm($word) . '*';
          }
        }
      }
      else {
        $placeholder_count++;
        $placeholders[] = '%L' . $placeholder_count . '%';
        $contents[] = $helper->escapeTerm($words[0]) . '*';
      }
    }
    $placeholder_string = implode(' OR ', $placeholders);
    $query->setQuery($placeholder_string, $contents);
    // set count of results to be fetched.
    $query->setStart(0)->setRows(20);
    $solrResult = $connector->search($query);
    $resultJson = $solrResult->getBody();
    $resultParsed = Json::decode($resultJson);
    $json_data = [];
    // Prepare json data.
    foreach ($resultParsed['response']['docs'] as $key => $doc) {
      $highlighted_title = $resultParsed['highlighting'][$doc['id']]['ts_title'][0];
      $json_data[$key]['value'] = $doc['ts_title'];
      $json_data[$key]['label'] = '<a href="' . $doc['site'] . 'node/' . $doc['its_nid'] . '" target="_blank"  class="search-result-title">' . Unicode::ucwords($highlighted_title) . '</a>';
    }
    return new JsonResponse($json_data);

  }

}
