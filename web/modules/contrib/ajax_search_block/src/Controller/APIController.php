<?php

namespace Drupal\ajax_search_block\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for api routes.
 */
class APIController extends ControllerBase {

  /**
   * The path alias.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $pathAlias;
  protected $db;

  public function __construct(AliasManager $pathalias, Connection $database) {
    $this->pathAlias = $pathalias;
    $this->db = $database;
  }
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('database')
    );
  }
  /**
   * Callback for `api/get_pages` API method.
   */
  public function get_pages( Request $request ) {
  	$params = $request->query->all();
  	if (count($params) > 0 && !empty($params['t'])) {
			$block_settings = isset($params['key']) ? $params['key'] : NULL;
  		$response['nodes'] = $this->_get_all_nodes_and_taxonomies($params['t'], $params['key']);
  	}
    $response['method'] = 'GET';

    return new JsonResponse( $response );
  }

  /**
   * Callback for `api/get_alias` API method.
   */
  public function get_alias( Request $request ) {
  	$params = $request->query->all();
  	if (count($params) > 0 && !empty($params['path'])) {
  		$path_alias = $this->pathAlias->getAliasByPath($params['path']);
  		$response['url'] = $path_alias;
  	}
    $response['method'] = 'GET';

    return new JsonResponse( $response );
  }

 /**
 * Implements _get_all_nodes_and_taxonomies().
 *
 * @param ($text) search text
 *
 * @return object.
 */
	public function _get_all_nodes_and_taxonomies($text, $block_instance_key) {
		$block_config = $this->config($block_instance_key . '.settings');
		if($block_config !== NULL){
			$node_types = (!empty($block_config->get('node_types'))) ? $block_config->get('node_types') : NULL;
			$vocabulary_vids = (!empty($block_config->get('taxonomy_types'))) ? $block_config->get('taxonomy_types') : NULL;
		}
		else{
			$config = $this->config('ajax_search_block.settings');
			$node_types = (!empty($config->get('node_types_selected'))) ? $config->get('node_types_selected') : NULL;
			$vocabulary_vids = (!empty($config->get('taxonomy_types_selected'))) ? $config->get('taxonomy_types_selected') : NULL;
		}

		$query_nodes = $this->db->select('node_field_data', 'nfd');
		$query_nodes->addField('nfd', 'nid', 'drupal_id');
		$query_nodes->addField('nfd', 'title', 'result');
		$query_nodes->addExpression("concat('/node/', nid)", "path");
		$query_nodes->condition('nfd.title', '%' . db_like($text) . '%', 'LIKE');
		$query_nodes->condition('nfd.type', $node_types, 'IN');

		$query_terms = $this->db->select('taxonomy_term_field_data', 'ttfd');
		$query_terms->addField('ttfd', 'tid', 'drupal_id');
		$query_terms->addField('ttfd', 'name', 'result');
		$query_terms->addExpression("concat('/taxonomy/term/', tid)", "path");
		$query_terms->condition('ttfd.name', '%' . db_like($text) . '%', 'LIKE');
		$query_terms->condition('ttfd.vid', $vocabulary_vids, 'IN');

		$query_terms->orderBy('result');

		if (count( $node_types) > 0 && count($query_terms) > 0) {
			$query_terms->union($query_nodes, 'DISTINCT');
			$pages = $query_terms->execute()->fetchAll();
		}
    elseif (count( $node_types ) > 0) {
			$pages = $query_nodes->execute()->fetchAll();
		}
    elseif (count( $query_terms ) > 0) {
			$pages = $query_terms->execute()->fetchAll();
		}

		return $pages;
	}
}
