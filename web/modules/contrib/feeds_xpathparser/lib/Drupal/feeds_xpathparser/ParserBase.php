<?php

/**
 * @file
 * Contains \Drupal\feeds_xpathparser\ParserBase.
 */

namespace Drupal\feeds_xpathparser;

use Drupal\Core\Form\FormInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedPluginFormInterface;
use Drupal\feeds\FetcherResultInterface;
use Drupal\feeds\FeedsParserResult;
use Drupal\feeds\Plugin\ParserBase as FeedsParserBase;

/**
 * Base class for the HTML and XML parsers.
 */
abstract class ParserBase extends FeedsParserBase implements FormInterface, FeedPluginFormInterface {

  /**
   * The mappings to return raw XML for.
   *
   * @var array
   */
  protected $rawXML = array();

  /**
   * The DOMDocument to parse.
   *
   * @var \DOMDocument
   */
  protected $doc;

  /**
   * The DOMXpath object to use for XPath queries.
   *
   * @var \DOMXpath
   */
  protected $xpath;

  /**
   * Classes that use ParserBase must implement this.
   *
   * @param array $feed_config
   *   The configuration for the source.
   * @param \Drupal\feeds\FetcherResultInterface $fetcher_result
   *   A FetcherResultInterface object.
   *
   * @return \DOMDocument
   *   The DOMDocument to perform XPath queries on.
   */
  abstract protected function setup(array $feed_config, FetcherResultInterface $fetcher_result);

  /**
   * Returns the raw node value.
   *
   * @param \DOMNode $node
   *   The DOMNode to convert to a string.
   *
   * @return string
   *   The string representation of the DOMNode.
   *
   * @todo Refactor.
   */
  abstract protected function getRaw(\DOMNode $node);

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $feed_config = $feed->getConfigFor($this);
    $state = $feed->state(FEEDS_PARSE);

    if (empty($feed_config)) {
      $feed_config = $this->getConfig();
    }

    $this->doc = $this->setup($feed_config, $fetcher_result);

    $parser_result = new FeedsParserResult();

    $mappings = $this->getOwnMappings();
    $this->rawXML = array_keys(array_filter($feed_config['raw_xml']));
    // Set link.
    $fetcher_config = $feed->getConfigFor($this->importer->fetcher);
    $parser_result->link = $fetcher_config['source'];

    $this->xpath = new DOMXPath($this->doc);
    $config = array();
    $config['debug'] = array_keys(array_filter($feed_config['debug']));
    $config['errors'] = $feed_config['errors'];

    $this->xpath->setConfig($config);

    $context_query = '(' . $feed_config['context'] . ')';
    if (empty($state->total)) {
      $state->total = $this->xpath->namespacedQuery('count(' . $context_query . ')', 'count', $this->doc);
    }

    $start = $state->pointer ? $state->pointer : 0;
    $limit = $start + $this->importer->getLimit();
    $end = ($limit > $state->total) ? $state->total : $limit;
    $state->pointer = $end;

    $context_query .= "[position() > $start and position() <= $end]";

    $progress = $state->pointer ? $state->pointer : 0;

    $all_nodes = $this->xpath->namespacedQuery($context_query, 'context');

    foreach ($all_nodes as $node) {
      // Invoke a hook to check whether the domnode should be skipped.
      if (in_array(TRUE, module_invoke_all('feeds_xpathparser_filter_domnode', $node, $this->doc, $feed), TRUE)) {
        continue;
      }

      $parsed_item = $variables = array();
      foreach ($feed_config['sources'] as $element_key => $query) {
        // Variable substitution.
        $query = strtr($query, $variables);
        // Parse the item.
        $result = $this->parseSourceElement($query, $node, $element_key);
        if (isset($result)) {
          if (!is_array($result)) {
            $variables['$' . $mappings[$element_key]] = $result;
          }
          else {
            $variables['$' . $mappings[$element_key]] = '';
          }
          $parsed_item[$element_key] = $result;
        }
      }
      if (!empty($parsed_item)) {
        $parser_result->items[] = $parsed_item;
      }
    }

    $state->progress($state->total, $progress);
    unset($this->doc);
    unset($this->xpath);
    return $parser_result;
  }

  /**
   * Parses one item from the context array.
   *
   * @param string $query
   *   An XPath query.
   * @param \DOMNode $context
   *   The current context DOMNode .
   * @param string $source
   *   The name of the source for this query.
   *
   * @return array
   *   An array containing the results of the query.
   */
  protected function parseSourceElement($query, $context, $source) {

    if (empty($query)) {
      return;
    }

    $node_list = $this->xpath->namespacedQuery($query, $source, $context);

    // Iterate through the results of the XPath query. If this source is
    // configured to return raw xml, make it so.
    if ($node_list instanceof \DOMNodeList) {
      $results = array();
      if (in_array($source, $this->rawXML)) {
        foreach ($node_list as $node) {
          $results[] = $this->getRaw($node);
        }
      }
      else {
        foreach ($node_list as $node) {
          $results[] = $node->nodeValue;
        }
      }
      // Return single result if so.
      if (count($results) === 1) {
        return $results[0];
      }
      // Empty result returns NULL, that way we can check.
      elseif (empty($results)) {
        return;
      }
      else {
        return $results;
      }
    }
    // A value was returned directly from namespacedQuery().
    else {
      return $node_list;
    }
  }

  protected function baseForm(array $form, array $form_state, array $config) {

    $mappings = $this->importer->processor->getMappings();

    $targets = $this->importer->processor->getMappingTargets();

    if (empty($mappings)) {
      // Detect if Feeds menu structure has changed. This will take a while to
      // be released, but since I run dev it needs to work.
      $feeds_base = 'admin/structure/feeds/manage/';
      $form['xpath']['error_message']['#markup'] = '<div class="help">' . t('No XPath mappings are defined. Define mappings !link.', array('!link' => l(t('here'), $feeds_base . $this->id . '/mapping'))) . '</div><br />';
      return $form;
    }
    $form['context'] = array(
      '#type' => 'textfield',
      '#title' => t('Context'),
      '#required' => TRUE,
      '#description' => t('This is the base query, all other queries will run in this context.'),
      '#default_value' => $config['context'],
      '#maxlength' => 1024,
      '#size' => 80,
      '#required' => TRUE,
    );

    $uniques = $this->getUniques();

    if (!empty($uniques)) {
      $items = array(
        format_plural(count($uniques),
          t('Field <strong>!column</strong> is mandatory and considered unique: only one item per !column value will be created.',
            array('!column' => implode(', ', $uniques))),
          t('Fields <strong>!columns</strong> are mandatory and values in these columns are considered unique: only one entry per value in one of these columns will be created.',
            array('!columns' => implode(', ', $uniques)))),
      );
      $form['help']['#markup'] = '<div class="help">' . theme('item_list', array('items' => $items)) . '</div>';
    }

    $form['sources'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
    );

    $variables = array();
    foreach ($this->getOwnMappings() as $source => $target) {
      $form['sources'][$source] = array(
        '#type' => 'textfield',
        '#title' => check_plain($targets[$target]['name']),
        '#description' => t('The XPath query to run.'),
        '#default_value' => isset($config['sources'][$source]) ? $config['sources'][$source] : '',
        '#maxlength' => 1024,
        '#size' => 80,
      );
      if (!empty($variables)) {
        $variable_text = format_plural(count($variables),
          t('The variable %variable is available for replacement.', array('%variable' => implode(', ', $variables))),
          t('The variables %variable are available for replacement.', array('%variable' => implode(', ', $variables)))
        );
        $form['sources'][$source]['#description'] .= '<br />' . $variable_text;
      }
      $variables[] = '$' . $target;
    }

    $form['settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    $form['raw_xml_settings'] = array(
      '#type' => 'details',
      '#group' => 'settings',
      '#title' => t('Raw XML'),
      '#collapsed' => TRUE,
      '#tree' => FALSE,
    );

    $form['raw_xml_settings']['raw_xml'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select the queries you would like to return raw XML or HTML'),
      '#options' => $this->getOwnMappings(TRUE),
      '#default_value' => $config['raw_xml'],
    );
    $form['debug'] = array(
      '#type' => 'details',
      '#group' => 'settings',
      '#title' => t('Debug'),
      '#collapsed' => TRUE,
      '#tree' => FALSE,
    );
    $form['debug']['errors'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show error messages.'),
      '#default_value' => $config['errors'],
    );
    $form['debug']['debug'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Debug query'),
      '#options' => array_merge(array('context' => t('Context')), $this->getOwnMappings(TRUE)),
      '#default_value' => $config['debug'],
    );

    if (extension_loaded('tidy')) {
      $form['debug']['tidy'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use Tidy'),
        '#description' => t('The Tidy PHP extension has been detected.
                              Select this to clean the markup before parsing.'),
        '#default_value' => $config['tidy'],
      );
      $form['debug']['tidy_encoding'] = array(
        '#type' => 'textfield',
        '#title' => t('Tidy encoding'),
        '#description' => t('Set the encoding for tidy. See the !phpdocs for possible values.', array('!phpdocs' => l(t('PHP docs'), 'http://www.php.net/manual/en/tidy.parsestring.php/'))),
        '#default_value' => $config['tidy_encoding'],
        '#states' => array(
          'visible' => array(
            ':input[name="tidy"],:input[name="parser[tidy]"]' => array(
              'checked' => TRUE,
            ),
          ),
        ),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function feedForm(array $form, array &$form_state, FeedInterface $feed) {
    if (!$this->config['allow_override']) {
      return $form;
    }

    if ($feed_config = $feed->getConfigFor($this)) {
      $form['parser'] = $this->baseForm(array(), $form_state, $feed_config);
    }
    else {
      $form['parser'] = $this->baseForm(array(), $form_state, $this->config);
    }

    $form['parser']['#type'] = 'details';
    $form['parser']['#group'] = 'advanced';
    $form['parser']['#title'] = t('XPath parser');
    $form['parser']['#weight'] = 1000;

    $form['parser']['context']['#parents'] = array('parser', 'context');
    $form['parser']['sources']['#parents'] = array('parser', 'sources');
    $form['parser']['raw_xml_settings']['raw_xml']['#parents'] = array('parser', 'raw_xml');
    $form['parser']['debug']['debug']['#parents'] = array('parser', 'debug');
    $form['parser']['debug']['errors']['#parents'] = array('parser', 'errors');
    if (isset($form['parser']['debug']['tidy'])) {
      $form['parser']['debug']['tidy']['#parents'] = array('parser', 'tidy');
      $form['parser']['debug']['tidy_encoding']['#parents'] = array('parser', 'tidy_encoding');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->getConfig();
    $form = $this->baseForm($form, $form_state, $config);

    $form['context']['#required'] = FALSE;

    $form['allow_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow source configuration override'),
      '#description' => t('This setting allows feeds to specify their own XPath values for the context and sources.'),
      '#default_value' => $config['allow_override'],
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function configDefaults() {
    return array(
      'sources' => array(),
      'raw_xml' => array(),
      'context' => '',
      'debug' => array(),
      'errors' => FALSE,
      'tidy' => FALSE,
      'tidy_encoding' => 'UTF8',
      'allow_override' => FALSE,
    );
  }

  /**
   * Overrides parent::feedFormValidate().
   *
   * If the values of this source are the same as the base config we set them to
   * blank so that the values will be inherited from the importer defaults.
   *
   * @todo Do this differently.
   */
  public function feedFormValidate(array $form, array &$form_state, FeedInterface $feed) {
    if ($this->config['allow_override']) {
      $config = $this->getConfig();
      $allow_override = $config['allow_override'];

      $values = $form_state['values']['parser'];

      unset($config['allow_override']);
      $this->kSort($values);
      $this->kSort($config);

      if ($values === $config || !$allow_override) {
        $form_state['values']['parser'] = array();
        return;
      }

      $parser_form_state = array();
      $parser_form_state['values'] =& $form_state['values']['parser'];
      $parser_form =& $form['parser'];

      $this->validateForm($parser_form, $parser_form_state);
    }
  }

  /**
   * Recursivly sorts an array.
   *
   * Also casts integer string to integers.
   *
   * @param array $array
   *   The array to sort.
   */
  protected function kSort(&$array) {
    ksort($array);
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        $this->kSort($value);
      }
      elseif (ctype_digit($value)) {
        $value = (int) $value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' . "\n<items></items>");
    $use_errors = $this->errorStart();

    $form_state['values']['context'] = trim($form_state['values']['context']);
    if (!empty($form_state['values']['context'])) {
      $result = $xml->xpath($form_state['values']['context']);
    }
    $error = libxml_get_last_error();

    // Error code 1219 is undefined namespace prefix.
    // Our sample doc doesn't have any namespaces let alone the one they're
    // trying to use.
    if ($error && $error->code != 1219) {
      form_error($form['context'], t('There was an error with the XPath selector: %error', array('%error' => $error->message)));
      libxml_clear_errors();
    }
    foreach ($form_state['values']['sources'] as $key => &$query) {
      $query = trim($query);

      if (!empty($query)) {
        $result = $xml->xpath($query);
        $error = libxml_get_last_error();
        if ($error && $error->code != 1219) {
          $variable_present = FALSE;
          // Our variable substitution options can cause syntax errors, check
          // if we're doing that.
          if ($error->code == 1207) {
            foreach ($this->getOwnMappings() as $target) {
              if (strpos($query, '$' . $target) !== FALSE) {
                $variable_present = TRUE;
                break;
              }
            }
          }
          if (!$variable_present) {
            form_error($form['sources'][$key], t('There was an error with the XPath selector: %error', array('%error' => $error->message)));
            libxml_clear_errors();
          }
        }
      }
    }

    $this->errorStop($use_errors, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    $mappings = $this->getOwnMappings();
    $next = 0;
    if (!empty($mappings)) {
      // Mappings can be re-ordered, so find the max.
      foreach (array_keys($mappings) as $key) {
        list(, $index) = explode(':', $key);
        if ($index > $next) {
          $next = $index;
        }
      }
      $next++;
    }

    return array(
      'xpathparser:' . $next => array(
        'name' => t('XPath Expression'),
        'description' => t('Allows you to configure an XPath expression that will populate this field.'),
      ),
    ) + parent::getMappingSources();
  }

  /**
   * Gets the unique mappings targets that are used by this parser.
   *
   * @return array
   *   An array of mappings keyed source => target.
   */
  protected function getUniques() {
    $uniques = array();
    $targets = $this->importer->processor->getMappingTargets();
    foreach ($this->importer->processor->getMappings() as $mapping) {
      if (!empty($mapping['unique']))
      $uniques[$mapping['source']] = $targets[$mapping['target']]['name'];
    }

    return $uniques;
  }

  /**
   * Gets the mappings that are defined by this parser.
   *
   * The mappings begin with "xpathparser:".
   *
   * @return array
   *   An array of mappings keyed source => target.
   */
  protected function getOwnMappings($label = FALSE) {
    $mappings = $this->filterMappings($this->importer->processor->getMappings());
    if ($label) {
      $targets = $this->importer->processor->getMappingTargets();
      foreach ($mappings as $source => $target) {
        $mappings[$source] = $targets[$target]['name'];
      }
    }

    return $mappings;
  }

  /**
   * Filters mappings, returning the ones that belong to us.
   *
   * @param array $mappings
   *   A mapping array from a processor.
   *
   * @return array
   *   An array of mappings keyed source => target.
   */
  protected function filterMappings(array $mappings) {
    $our_mappings = array();
    foreach ($mappings as $mapping) {
      if (strpos($mapping['source'], 'xpathparser:') === 0) {
        $our_mappings[$mapping['source']] = $mapping['target'];
      }
    }
    return $our_mappings;
  }

  /**
   * Starts custom error handling.
   *
   * @return bool
   *   The previous value of use_errors.
   */
  protected function errorStart() {
    libxml_clear_errors();
    return libxml_use_internal_errors(TRUE);
  }

  /**
   * Stops custom error handling.
   *
   * @param bool $use
   *   The previous value of use_errors.
   * @param bool $print
   *   (Optional) Whether to print errors to the screen. Defaults to TRUE.
   */
  protected function errorStop($use, $print = TRUE) {
    if ($print) {
      foreach (libxml_get_errors() as $error) {
        switch ($error->level) {
          case LIBXML_ERR_WARNING:
          case LIBXML_ERR_ERROR:
            $type = 'warning';
            break;

          case LIBXML_ERR_FATAL:
            $type = 'error';
            break;
        }
        $args = array(
          '%error' => trim($error->message),
          '%num' => $error->line,
          '%code' => $error->code,
        );
        $message = t('%error on line %num. Error code: %code', $args);
        drupal_set_message($message, $type, FALSE);
      }
    }
    libxml_clear_errors();
    libxml_use_internal_errors($use);
  }

}
