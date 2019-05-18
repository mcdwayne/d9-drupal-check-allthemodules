<?php

namespace Drupal\quora;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Quora data processing functions.
 */
class QuoraDataProcess implements ContainerFactoryPluginInterface {

  /**
   * The config object for 'quora.admin'.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Logger object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $loggerChannelFactory;

  /**
   * QuoraDataProcess constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config object.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_channel_factory
   *   Logger Object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger_channel_factory) {
    $this->config = $config_factory;
    $this->loggerChannelFactory = $logger_channel_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Builds result content for block.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node object.
   * @param array $conf
   *   Display configuration values.
   *
   * @return array|null
   *   Array of results.
   */
  public function buildContent(Node $node, array $conf) {
    $quoraTagField = $this->fetchField($node);
    $quoraTagString = $this->preprocessTagTerms($node, $quoraTagField);
    $this->filterTagTerms($quoraTagString, $conf);
    $results = $this->getResults($quoraTagString);
    if (!$results) {
      drupal_set_message(t('No results were found on Quora for the given set of keywords/tags.'), 'error', FALSE);
      return NULL;
    }
    return $this->processResults($results, $conf);

  }

  /**
   * Get results using Google CSE.
   *
   * @param string $query
   *   String to search.
   *
   * @return array|bool
   *   Results or FALSE.
   */
  public function getResults($query) {
    $config = $this->config->get('quora.admin');
    // We want to make sure about search on quora.com domain.
    $query .= " site:quora.com";
    $query = trim($query);

    $api = $config->get('google_cse_api');
    $cx = $config->get('google_cse_cx');
    if ($api && $cx) {
      // Use api to get results.
      $web_results = json_decode(@file_get_contents('https://www.googleapis.com/customsearch/v1?key=' . $api . '&cx=' . $cx . '&q=' . urlencode($query)));
      if (property_exists($web_results, 'items')) {
        foreach ($web_results->items as $result) {
          $quora_results[] = array(
            'title' => $result->title,
            'snippet' => $result->snippet,
            'url' => $result->link,
          );
        }
        return $quora_results;
      }
    }
    else {
      $this->loggerChannelFactory->get('quora')->error('Unable to fetch results with query @query',
        array(
          '@query' => urlencode($query),
        ));
      return FALSE;
    }

  }

  /**
   * Returns field which will be used as tag field by quora.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node object.
   *
   * @return string
   *   Tag field.
   */
  public function fetchField(Node $node) {
    $config = $this->config->get('quora.admin');
    $mappedField = $config->get($node->getType() . '_fields');
    if ($mappedField == 'auto') {
      return 'title';
    }
    elseif ($mappedField == 'field_tags') {
      return 'field_tags';
    }
    $mappedFieldValue = $node->{$mappedField}->getValue();
    if (isset($mappedFieldValue) && !empty($mappedFieldValue)) {
      return $mappedField;
    }
  }

  /**
   * Returns array of preprocessed tags according to sensitivity.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node object.
   * @param string $quoraTagField
   *   Tag field.
   *
   * @return null|string
   *   Tag terms.
   */
  public function preprocessTagTerms(Node $node, $quoraTagField) {
    if ($quoraTagField != 'field_tags') {
      $data = $node->{$quoraTagField}->getValue();
    }
    else {
      $data = $node->field_tags->referencedEntities();
    }
    // Formation of Data string.
    switch (gettype($data)) {
      case 'string':
        // Do nothing.
        break;

      case 'array':
        $str = '';
        if (!is_object($data[0])) {
          $str = $data[0]['value'];
        }
        else {
          foreach ($data as $term) {
            $str .= $term->getName() . ' ';
          }
        }
        if ($str) {
          $data = NULL;
          $data = $str;
        }
        else {
          $this->loggerChannelFactory->get('quora')->notice('Unsupported fieldtype selected as quoraTagField');
          // Selecting title as quoraTagField.
          $data = $node->title->getValue();
        }
        break;

      case 'object':
        $str = '';
        if (isset($data->type)) {
          $str = $this->preprocessTagTerms($data, $this->fetchField($data));
        }

        if ($str) {
          $data = NULL;
          $data = $str;
        }
        else {
          $this->loggerChannelFactory->get('quora')->notice('Unsupported fieldtype selected as quoraTagField');
          // Selecting title as quoraTagField.
          $data = $node->title->getValue();
        }
        break;

      default:
        $this->loggerChannelFactory->get('quora')->notice('Unsupported fieldtype selected as quoraTagField');
        // Selecting title as quoraTagField.
        $data = $node->title->getValue();
        break;
    }
    return $data;

  }

  /**
   * Filter the tag terms based on user defined options.
   *
   * @param string $data
   *   Tag terms.
   * @param array $conf
   *   Search configurations.
   *
   * @return string
   *   Filtered tag terms.
   */
  public function filterTagTerms($data, array $conf) {
    // We have string in variable data.
    $data = preg_replace('/[^\p{L}\p{N}\ ]/', '', $data);
    // Process exclude list.
    $ex = $conf['exclude'];
    if ($ex) {
      $ex = preg_replace('/[^\p{L}\p{N}\ \,]/', '', $ex);
      $ex = explode(',', $ex);
    }
    $data = str_replace($ex, '', $data);
    $terms = explode(' ', $data);
    $count = count($terms);
    switch ($conf['search_sensitivity']) {

      case 1:
        // 3 Words.
        if ($count > 3) {
          $terms = array_slice($terms, 0, 3);
        }
        break;

      case 2:
        // 5 Words.
        if ($count > 5) {
          $terms = array_slice($terms, 0, 5);
        }
        break;

      case 3:
        // 7 Words.
        if ($count > 7) {
          $terms = array_slice($terms, 0, 7);
        }
        break;

      default:
        // Maximum words.
        while ($count >= 10) {
          $count = $count / 2;
        }
        $terms = array_slice($terms, 0, $count);
        break;
    }
    $data = implode(' ', $terms);
    // Process include string.
    $in = $conf['include'];
    if ($in) {
      $in = preg_replace('/[^\p{L}\p{N}\ ]/', '', $in);
    }
    $data .= ' ' . $in;
    $data = preg_replace('/\s+/', ' ', $data);
    return $data;

  }

  /**
   * Process results array.
   *
   * @param array $results
   *   Search results.
   * @param array $conf
   *   Search configurations.
   *
   * @return array
   *   Processed Results.
   */
  public function processResults(array $results, array $conf) {
    $results = array_slice($results, 0, $conf['no_questions']);
    // Preprocess description snippet to be displayed.
    if ($conf['description'] == 'disable') {
      foreach ($results as $key => $result) {
        unset($results[$key]['snippet']);
      }
    }
    else {
      $size = $conf['description_size'];
      if ($size) {
        foreach ($results as $key => $result) {
          $results[$key]['snippet'] = text_summary($result['snippet'], NULL, $size) . '..';
        }
      }
    }

    return $results;
  }

}
