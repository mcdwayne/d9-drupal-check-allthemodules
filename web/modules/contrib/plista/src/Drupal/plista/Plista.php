<?php

/**
 * @file
 * Contains \Drupal\plista\Plista.
 */

namespace Drupal\plista;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Config;
use Drupal\Core\Path\AliasManager;
use Drupal\node\Entity\Node;
use GuzzleHttp\Exception\RequestException;

class Plista {

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var AliasManager
   */
  protected $aliasManager;


  /**
   * @param $node
   * @return static
   */
  public static function create($node) {

    $config = \Drupal::config('plista.settings');
    $aliasManager = \Drupal::service('path.alias_manager');

    return new static($node, $config, $aliasManager);
  }

  /**
   * @param Node $node
   * @param Config $config
   * @param AliasManager $aliasManager
   */
  public function __construct(Node $node, Config $config, AliasManager $aliasManager) {

    $this->node = $node;
    $this->config = $config;
    $this->aliasManager = $aliasManager;
  }

  /**
   * Returns the plista js config
   *
   * @return array
   */
  protected function attachJS() {

    $plista_basic = $this->config->get('plista_basic');

    $pages = drupal_strtolower($plista_basic['plista_hidden_paths']);

    // Compare the lowercase path alias (if any) and internal path.
    $path = current_path();
    $path_alias = Unicode::strtolower($this->aliasManager->getPathAlias($path));
    $page_match = drupal_match_path($path_alias, $pages) || (($path != $path_alias) && drupal_match_path($path, $pages));

    // Check for node status to ensure all fields are filled with useful values.
    if (
      !is_array($plista_basic['plista_node_types']) ||
      !in_array($this->node->getType(), $plista_basic['plista_node_types']) ||
      $page_match || !$this->node->get('status')
    ) {
      return array();
    }

    $token = \Drupal::token();

    // Build the data array for the current node.
    $data = array(
      'objectid' => $this->node->nid,
      'title' => trim(SafeMarkup::checkPlain($token->replace($plista_basic['plista_field_title'], array('node' => $this->node)))),
      'text' => trim(drupal_substr(SafeMarkup::checkPlain($token->replace($plista_basic['plista_field_text'], array('node' => $this->node))), 0, 245)),
      'url' => url('node/' . $this->node->nid, array('absolute' => TRUE)),
      'img' => $token->replace($plista_basic['plista_field_img'], array('node' => $this->node)),
      'category' => $token->replace($plista_basic['plista_field_category'], array('node' => $this->node)),
    );

    return array(
      'js' => array(
        array(
          'type' => 'setting',
          'data' => array('plista' => $data),
        )
      ),
      'library' => array(
        array('plista', 'plista.behavior')
      ),
    );

  }

  /**
   * Shows the plista widget.
   *
   * @return string
   *   Rendered HTML of the widget
   */
  public function view() {

    $plista_basic = $this->config->get('plista_basic');

    return array(
      '#markup' => '<div id="' . SafeMarkup::checkPlain($plista_basic['plista_widgetname']) . '"></div>',
      '#attached' => $this->attachJS(),
    );
  }

  /**
   * Updates the current node
   */
  public function update() {
    $this->performUpdate('update');
  }

  /**
   * Deletes the current node
   */
  public function delete() {
    $this->performUpdate('delete');
  }

  /**
   * Internal method to perform update/delete action to plista
   *
   * @param $action
   */
  protected function performUpdate($action) {

    $valid_actions = array('update', 'delete');

    // Performs updates just to selected node types.
    $plista_basic = $this->config->get('plista_basic');

    $selected_node_types = isset($plista_basic['plista_node_types']) ? array_values($plista_basic['plista_node_types']) : array();
    if (!in_array($this->node->type, $selected_node_types) || !in_array($action, $valid_actions)) {
      return;
    }

    // Performs update just if domain id and api key are specified.
    $plista_advanced = $this->config->get('plista_advanced');

    if (!isset($plista_advanced['plista_domain_id']) || empty($plista_advanced['plista_domain_id']) ||
      !isset($plista_advanced['plista_api_key']) || empty($plista_advanced['plista_api_key']) ||
      !isset($plista_advanced['plista_update_url']) || empty($plista_advanced['plista_update_url'])
    ) {
      return;
    }

    $url = url($plista_advanced['plista_update_url'] . $action . '/' . $this->node->nid,
      array(
        'external' => TRUE,
        'query' => array(
          'domainid' => $plista_advanced['plista_domain_id'],
          'apikey' => $plista_advanced['plista_api_key'],
          'status' => FALSE,
        ),
      )
    );

    $request = \Drupal::httpClient()->get($url);

    try {
      $response = $request->send();

      switch ($response->getStatusCode()) {
        case "200":
          watchdog('plista', 'Action @action successful', array('@action' => $action), WATCHDOG_INFO);
          break;

        case "400":
          watchdog('plista', 'Unknown node: @title', array('@title' => $this->node->title), WATCHDOG_WARNING);
          break;

        case "403":
          watchdog('plista', 'Access denied', array(), WATCHDOG_WARNING);
          break;

        default:
          watchdog('plista', 'Unknown Response-Code: @code', array('@code' => $request->code), WATCHDOG_WARNING);
          break;
      }
    } catch (RequestException $e) {
      watchdog_exception('plista', $e);
    }

  }
}
