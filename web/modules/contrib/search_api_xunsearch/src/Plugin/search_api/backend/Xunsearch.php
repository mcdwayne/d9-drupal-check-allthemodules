<?php

namespace Drupal\xunsearch\Plugin\search_api\backend;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggestion\SuggestionFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Indexes and searches items using the Xunsearch.
 *
 * @SearchApiBackend(
 *   id = "search_api_xunsearch",
 *   label = @Translation("Xunsearch"),
 *   description = @Translation("Use Xunsearch as search backend.")
 * )
 */
class Xunsearch extends BackendPluginBase implements PluginFormInterface {
	use PluginFormTrait;
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static(
			$configuration,
			$plugin_id,
			$plugin_definition
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function viewSettings() {
		return [
			[
				'label' => $this->t('indexd port'),
				'info' => sprintf('%s:%s', $this->configuration['host'], $this->configuration['indexd_port'])
			],
			[
				'label' => $this->t('searchd port'),
				'info' => sprintf('%s:%s', $this->configuration['host'], $this->configuration['searchd_port'])
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
		$form['host']=[
			'#type' => 'textfield',
			'#title' => $this->t('Xunsearch server host'),
			'#required' => TRUE,
			'#default_value' => $this->configuration['host'],
		];
		$form['indexd_port']=[
			'#type' => 'number',
			'#title' => $this->t('indexd port'),
			'#required' => TRUE,
			'#default_value' => $this->configuration['indexd_port'],
		];
		$form['searchd_port']=[
			'#type' => 'number',
			'#title' => $this->t('searchd port'),
			'#required' => TRUE,
			'#default_value' => $this->configuration['searchd_port'],
		];
		$form['ini_path']=[
			'#type' => 'textfield',
			'#title' => $this->t('INI file path'),
			'#required' => TRUE,
			'#default_value' => $this->configuration['ini_path'],
		];
		$form['sdk_path']=[
			'#type' => 'textfield',
			'#title' => $this->t('Xunsearch PHP SDK path'),
			'#required' => TRUE,
			'#default_value' => $this->configuration['sdk_path'],
		];
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
		if(!empty($form_state->getValue('ini_path'))) {
			$dir=\Drupal::service('file_system')->realpath($form_state->getValue('ini_path'));
			if(!is_dir($dir)) {
				$form_state->setErrorByName('ini_path', $this->t('ini path %dir is not a writable directory.', ['%dir'=>$dir]));
			} else {
				try {
					$fname=tempnam($dir, 'xun');
					if($fname==FALSE||!preg_match('/^'.preg_quote($dir, '/').'/', $fname))
						throw new \Exception();
				} catch (\Exception $e) {
					$form_state->setErrorByName('ini_path', $this->t('ini path %dir is not a writable directory.', ['%dir'=>$form_state->getValue('ini_path')]));
				} finally {
					if($fname) unlink($fname);
				}
			}
		}

		if(!empty($form_state->getValue('sdk_path'))) {
			if($form_state->getValue('sdk_path')=='composer://') {
				if(!class_exists('XS'))
					$form_state->setErrorByName('sdk_path', $this->t('hightman/xunsearch package does not exist in vendor directory of Drupal root.'));
			} else {
				@set_include_path(get_include_path() . PATH_SEPARATOR . $form_state->getValue('sdk_path'));
				@include_once('lib/XS.php');
				if(!class_exists('XS'))
					$form_state->setErrorByName('sdk_path', $this->t('SDK path %dir does not define XS class.', ['%dir'=>$form_state->getValue('sdk_path').'/lib/XS.php']));
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function defaultConfiguration() {
		$private_path = \Drupal\Core\StreamWrapper\PrivateStream::basePath();
		if(empty($private_path)) $ini_path='';
		else $ini_path='private://';
		
		$sdk_path='';
		if(file_exists(DRUPAL_ROOT.'/vendor/hightman/xunsearch/lib/XS.class.php'))
			$sdk_path='composer://';
		else {
			$dirs=['/usr/lib64/xunsearch/sdk/php', '/usr/local/xunsearch/sdk/php'];
			foreach($dirs as $dir) {
				if(file_exists($dir.'/lib/XS.php'))
					$sdk_path=$dir;
			}
		}
		return [
			'host' => 'localhost',
			'indexd_port' => 8383,
			'searchd_port' => 8384,
			'ini_path' => $ini_path,
			'sdk_path' => $sdk_path,
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSupportedFeatures() {
		return ['search_api_facets', 'search_api_autocomplete'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDiscouragedProcessors() {
		return ['tokenizer', 'stemmer'];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getSpecialFields(IndexInterface $index, ItemInterface $item = NULL) {
		$fields = parent::getSpecialFields($index, $item);
		return ['search_api_id' => $fields['search_api_id']];
	}

	private static function typeToType($typeid) {
		switch($typeid) {
			case 'boolean':
				$result='string'; break;
			case 'date':
				$result='date'; break;
			case 'decimal':
			case 'integer':
				$result='numeric'; break;
			case 'string':
				$result='string'; break;
			case 'text':
				$result='body'; break;
			default:
				$result='string';
		}
		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addIndex(IndexInterface $index) {
		self::updateIndex($index);
	}

	/**
	 * {@inheritdoc}
	 */
	public function updateIndex(IndexInterface $index) {
		$indexid=$index->id();
		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $indexid);

		$content='';
		$content.="project.name = ".$indexid."\n";
		$content.="project.default_charset = UTF-8\n";
		if($this->configuration['host']=='localhost') {
			$content.=sprintf("server.index = %s\n", $this->configuration['indexd_port']);
			$content.=sprintf("server.search = %s\n", $this->configuration['searchd_port']);
		} else {
			$content.=sprintf("server.index = %s:%s\n", $this->configuration['host'], $this->configuration['indexd_port']);
			$content.=sprintf("server.search = %s:%s\n", $this->configuration['host'], $this->configuration['searchd_port']);
		}
		$content.="\n";

		$fields=$index->getFields();
		$fields+=$this->getSpecialFields($index);

		foreach($fields as $fid => $field) {
			$type=self::typeToType($field->getType());
			$content.=sprintf("[%s]\n", $fid);
			if($fid=='search_api_id')
				$content.="type = id\nindex = none\n";
			else {
				$content.=sprintf("type = %s\nindex = both\n", $type);
				if($type=='string' && preg_match('/id$/', $fid)) {
					$content.="tokenizer = full\n";
				}
			}
			$content.="\n";
		}

		file_put_contents(\Drupal::service('file_system')->realpath($configuration_file_path), $content);
	}

	/**
	 * {@inheritdoc}
	 */
	public function removeIndex($index) {
		$this->deleteAllIndexItems($index);

		$indexid=$index->id();
		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $indexid);;
		unlink(\Drupal::service('file_system')->realpath($configuration_file_path));
	}

	/**
	 * {@inheritdoc}
	 */
	public function indexItems(IndexInterface $index, array $items) {
		if($this->configuration['sdk_path']!=='composer://') {
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->configuration['sdk_path']);
			require_once('lib/XS.php');
		}

		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $index->id());
		$xs=new \XS(\Drupal::service('file_system')->realpath($configuration_file_path));
		$xs->index->openBuffer();

		$indexed=[];
		$fields=$index->getFields();
		$fields+=$this->getSpecialFields($index);
		foreach ($items as $id => $item) {
			try {
				$data=[];
				foreach($fields as $fid => $field) {
					if($fid=='search_api_id')
						$data[$fid]=$id;
					else {
						$field_entity=$item->getField($fid);
						if(!empty($field_entity)) {
							$data[$fid]=implode(' ',$field_entity->getValues());
						}
					}
				}
				$doc = new \XSDocument($data);
				$xs->index->update($doc);
				$indexed[] = $id;
			}
			catch (\Exception $e) {
				throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
			}
		}
		$xs->index->closeBuffer();
		unset($xs);
		return $indexed;
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteItems(IndexInterface $index, array $item_ids) {
		if($this->configuration['sdk_path']!=='composer://') {
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->configuration['sdk_path']);
			require_once('lib/XS.php');
		}

		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $index->id());
		$xs=new \XS(\Drupal::service('file_system')->realpath($configuration_file_path));
		$xs->index->del($item_ids);
		unset($xs);
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
		if($this->configuration['sdk_path']!=='composer://') {
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->configuration['sdk_path']);
			require_once('lib/XS.php');
		}

		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $index->id());
		$xs=new \XS(\Drupal::service('file_system')->realpath($configuration_file_path));
		$xs->index->clean();
		unset($xs);
	}

	private static function conjuctionToOp($conjuction='AND') {
		$conjuction=strtoupper($conjuction);
		switch($conjunction) {
			case 'OR': return 1;	// define('XS_CMD_QUERY_OP_OR',		1);
			case 'XOR': return 3;	// define('XS_CMD_QUERY_OP_XOR',	3);
			case 'AND':
			default: return 0;	// define('XS_CMD_QUERY_OP_AND',	0);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(QueryInterface $query) {
		if($this->configuration['sdk_path']!=='composer://') {
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->configuration['sdk_path']);
			require_once('lib/XS.php');
		}

		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $query->getIndex()->id());
		$xs=new \XS(\Drupal::service('file_system')->realpath($configuration_file_path));
		$search=$xs->search;

		// If used in status page, then return the count of database
		if($query->hasTag('server_index_status')) {
			$results=$query->getResults();
			$results->setResultCount($search->getDbTotal());
			return;
		}

		// Compose sort condition.
		$sorts=$query->getSorts();
		if(!empty($sorts)) {
			$sorts_array=[];
			foreach($sorts as $fid => $order) {
				$sorts_array[$fid]=($order==QueryInterface::SORT_DESC);
			}
			$search->setMultiSort($sorts_array);
		}

		// Compose limit options.
		$query_options=$query->getOptions();
		if (isset($query_options['offset']) || isset($query_options['limit'])) {
			$offset = isset($query_options['offset']) ? $query_options['offset'] : 0;
			$limit = isset($query_options['limit']) ? $query_options['limit'] : 1000000;
			$search->setLimit($limit, $offset);
		}

		// Compose facets fields
		if (isset($query_options['search_api_facets'])) {
			$facets_array=[];
			foreach($query_options['search_api_facets'] as $facet_id => $facet ) {
				$facets_array[]=$facet['field'];
			}
			$search->setFacets($facets_array, TRUE);
		}

		$results=$query->getResults();
		$keys=$query->getKeys();
		unset($keys['#conjunction']);

		// Push user-input keys
		$op=self::conjuctionToOp($query->getParseMode()->getConjunction());
		foreach($keys as $key) {
			$search->addQueryString($key, $op);
		}

		// Compose filter conditions
		$conditions=$query->getConditionGroup()->getConditions();
		if(count($conditions)) {
			foreach($conditions as $conditionGroup) {
				$subkeys=[];
				foreach($conditionGroup->getConditions() as $condition) {
					$search->addQueryTerm($condition->getField(), $condition->getValue(), $op);
				}
			}
		}

		// Execute search
		$docs=$search->search();
		if(!empty($query_options['skip result count']))
			$results->setResultCount($search->lastCount);
		foreach($docs as $doc) {
			$item = $this->getFieldsHelper()->createItem($query->getIndex(), $doc->search_api_id);
			$item->setScore($doc->rank());
			$results->addResultItem($item);
		}

		// Compose facet results
		if (isset($query_options['search_api_facets'])) {
			$facets_results=[];
			foreach($query_options['search_api_facets'] as $facet_id => $facet ) {
				foreach($search->getFacets($facet['field']) as $fid => $count) {
					$facets_results[$facet_id][]=['count' => $count, 'filter' => '"'.$fid.'"'];
				}
			}
			$results->setExtraData('search_api_facets', $facets_results);
		}
	}

	/**
	 * Retrieves autocompletion suggestions for some user input.
	 *
	 * @param \Drupal\search_api\Query\QueryInterface $query
	 *   A query representing the base search, with all completely entered words
	 *   in the user input so far as the search keys.
	 * @param \Drupal\search_api_autocomplete\SearchInterface $search
	 *   An object containing details about the search the user is on, and
	 *   settings for the autocompletion. See the class documentation for details.
	 *   Especially $search->getOptions() should be checked for settings, like
	 *   whether to try and estimate result counts for returned suggestions.
	 * @param string $incomplete_key
	 *   The start of another fulltext keyword for the search, which should be
	 *   completed. Might be empty, in which case all user input up to now was
	 *   considered completed. Then, additional keywords for the search could be
	 *   suggested.
	 * @param string $user_input
	 *   The complete user input for the fulltext search keywords so far.
	 *
	 * @return \Drupal\search_api_autocomplete\Suggestion\SuggestionInterface[]
	 *   An array of autocomplete suggestions.
	 *
	 * @see \Drupal\search_api_autocomplete\AutocompleteBackendInterface::getAutocompleteSuggestions()
	 */
	public function getAutocompleteSuggestions(QueryInterface $query, SearchInterface $search, $incomplete_key, $user_input) {
		if($this->configuration['sdk_path']!=='composer://') {
			set_include_path(get_include_path() . PATH_SEPARATOR . $this->configuration['sdk_path']);
			require_once('lib/XS.php');
		}

		$configuration_file_path=sprintf('%s/%s.ini', $this->configuration['ini_path'], $query->getIndex()->id());
		$xs=new \XS(\Drupal::service('file_system')->realpath($configuration_file_path));
		$search=$xs->search;

		$results=$query->getResults();
		$keys=$query->getKeys();
		unset($keys['#conjunction']);

		// Compose filter conditions
		$conditions=$query->getConditionGroup()->getConditions();
		if(count($conditions)) {
			foreach($conditions as $conditionGroup) {
				$subkeys=[];
				foreach($conditionGroup->getConditions() as $condition) {
					$subkeys[]=sprintf('%s:"%s"', $condition->getField(), $condition->getValue());
				}
				if(!empty($subkeys))
					$keys[]=sprintf(' (%s) ', implode(' '.$conditionGroup->getConjunction().' ', $subkeys));
			}
		}

		// Execute search
		$search->setQuery(implode(' '.$query->getParseMode()->getConjunction().' ', $keys));

		$suggestions = [];
		$factory = new SuggestionFactory($user_input);

		$results=$search->getExpandedQuery($user_input);
		foreach($results as $result) {
			$suggestions[] = $factory->createFromSuggestedKeys($result);
		}

		return $suggestions;
	}
}
