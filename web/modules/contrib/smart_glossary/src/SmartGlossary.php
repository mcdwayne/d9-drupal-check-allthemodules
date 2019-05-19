<?php

/**
 * @file
 * The main class of the Smart Glossary.
 */

namespace Drupal\smart_glossary;
use Drupal\Component\Utility\UrlHelper;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;
use EasyRdf_Resource;
use Drupal\semantic_connector\Api\SemanticConnectorSparqlApi;

/**
 * A collection of static functions offered by the Smart Glossary module.
 */
class SmartGlossary {

  protected static $instance;
  protected $config;
  protected $graphUri;
  protected $timeout;
  protected $defaultLanguage;
  protected $languages;
  /** @var SemanticConnectorSparqlApi $glossaryStore */
  protected $glossaryStore;

  /**
   * Constructor of the Smart Glossary class.
   *
   * @param $config SmartGlossaryConfig
   *   The configuration of the Smart Glossary.
   */
  protected function __construct($config) {
    $this->config = $config;
    $advanced_settings = $config->getAdvancedSettings();
    $this->graphUri = $advanced_settings['graph_uri'];
    $this->glossaryStore = $config->getConnection()->getApi();
    $this->languages = self::getAvailableLanguages($config);
    $this->defaultLanguage = $this->languages[0];
  }

  /**
   * Get a smart-glossary-instance (Singleton).
   *
   * @param $config SmartGlossaryConfig
   *   The configuration of the Smart Glossary.
   *
   * @return SmartGlossary
   *   The Smart Glossary instance.
   */
  public static function getInstance($config) {
    if (!isset(self::$instance)) {
      $object_name = __CLASS__;
      self::$instance = new $object_name($config);
    }
    return self::$instance;
  }

  public function available() {
    return $this->config->getConnection()->available();
  }

  /**
   * Returns data for the autocomplete field.
   *
   * @param string $string
   *   The search string
   * @param int $limit
   *   Optional; the maximum number of concepts to be found, default 15
   * @param string $language
   *   The language in which you want to search
   *
   * @return array
   *   A list of concepts with following parameters:
   *   - label: prefLabel of the concept
   *   - id: the concept ID
   *   - encoded: the slaged prefLabel for creating a friendly URL
   */
  public function autoComplete($string, $limit = 15, $language = '') {
    if (empty($string) || $this->glossaryStore === FALSE) {
      return array();
    }

    if (empty($language)) {
      $language = $this->defaultLanguage;
    }

    $query = "
      PREFIX skos:<http://www.w3.org/2004/02/skos/core#>

      SELECT DISTINCT ?concept ?label ?prefLabel
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        ?concept a skos:Concept.
        {
          ?concept skos:prefLabel ?label FILTER(regex(str(?label),'$string','i') && lang(?label) = '$language').
          ?concept skos:prefLabel ?prefLabel FILTER(lang(?prefLabel) = '$language').
        } UNION {
          ?concept skos:altLabel ?label FILTER(regex(str(?label),'$string','i') && lang(?label) = '$language').
          ?concept skos:prefLabel ?prefLabel FILTER(lang(?prefLabel) = '$language').
        }
      }
      ORDER BY ASC(?label)
      LIMIT $limit";

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return array();
    }

    // Sort the labels.
    $list_start = array();
    $list_middle = array();
    foreach ($rows as $data) {
      $label = $this->rebuiltLabel($data->label->getValue());
      $prefLabel = $this->rebuiltLabel($data->prefLabel->getValue());
      $label .= ($label == $prefLabel) ? '' : ' (' . $prefLabel . ')';
      if (stripos($label, $string) > 0) {
        $list_middle[] = array(
          'label' => $label,
          'url' => $this->createUrl($data->concept->getUri(), $prefLabel, $language),
        );
      }
      else {
        $list_start[] = array(
          'label' => $label,
          'url' => $this->createUrl($data->concept->getUri(), $prefLabel, $language),
        );
      }
    }

    usort($list_start, array($this, 'sortAlpha'));
    usort($list_middle, array($this, 'sortAlpha'));
    $list = array_merge($list_start, $list_middle);

    return $list;
  }

  /**
   * Get a list of concepts.
   *
   * @param string $char
   *   The starting letter of the concepts
   * @param string $language
   *   Optional; the language of the objects
   * @param int $limit
   *   Optional; The maximum number of items to receive.
   *   0 means no limit.
   *
   * @return array
   *   An array of concepts-objects
   */
  public function getList($char, $language = '', $limit = 0) {
    if ($this->glossaryStore === FALSE) {
      return array();
    }

    if (empty($language)) {
      $language = $this->defaultLanguage;
    }

    $prefLabel_filter = !empty($char) ? "regex(str(?prefLabel),'^ *$char','i') &&" : '';

    $query = "
      PREFIX skos:<http://www.w3.org/2004/02/skos/core#>

      SELECT DISTINCT ?concept ?prefLabel ?broaderLabel
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        ?concept a skos:Concept.
        ?concept skos:prefLabel ?prefLabel FILTER($prefLabel_filter lang(?prefLabel) = '$language').
        OPTIONAL {
          ?concept skos:broader ?broader.
          ?broader skos:prefLabel ?broaderLabel FILTER(lang(?broaderLabel) = '$language').
        }
      }";
    if ($limit > 0) {
      $query .= "LIMIT $limit";
    }

    // Offer the possibility to alter the query before its execution.
    $alter_context = array(
      'char' => $char,
      'language' => $language,
      'limit' => $limit,
    );
    \Drupal::moduleHandler()->alter('smart_glossary_list_query', $query, $alter_context);

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return array();
    }

    $concepts     = array();
    $last_concept = array();
    foreach ($rows as $data) {
      if (!isset($data->concept) || !isset($data->prefLabel)) {
        continue;
      }
      $concept_uri = $data->concept->getUri();
      $concepts[$concept_uri] = new \stdClass();
      $concepts[$concept_uri]->uri  = $data->concept->getUri();
      $concepts[$concept_uri]->prefLabel = $this->rebuiltLabel($data->prefLabel->getValue());
      $concepts[$concept_uri]->url = $this->createUrl($concept_uri, $data->prefLabel->getValue(), $language);
      if (!isset($concepts[$concept_uri]->broader)) {
        $concepts[$concept_uri]->broader = array();
      }
      if (isset($data->broaderLabel)) {
        $concepts[$concept_uri]->broader[] = $data->broaderLabel->getValue();
      }
      if (!isset($concepts[$concept_uri]->multiple)) {
        $concepts[$concept_uri]->multiple = FALSE;
      }
      if (!empty($last_concept) && $concept_uri != $last_concept['uri'] && strtolower($data->prefLabel->getValue()) == strtolower($last_concept['prefLabel'])) {
        $concepts[$last_concept['uri']]->multiple = TRUE;
        $concepts[$concept_uri]->multiple = TRUE;
      }

      $last_concept = array(
        'uri' => $concept_uri,
        'prefLabel'  => $data->prefLabel->getValue(),
      );
    }

    uasort($concepts, array($this, 'sortAlpha'));
    return $concepts;
  }

  /**
   * Get a single concept.
   *
   * @param string $uri
   *   The URI of the concept
   * @param string $language
   *   Optional; the language of the objects
   *
   * @return object
   *   The concept-object or NULL if there was a problem
   */
  public function getConcept($uri, $language = '') {
    if (!UrlHelper::isValid($uri) || $this->glossaryStore === FALSE) {
      return NULL;
    }

    if (empty($language)) {
      $language = $this->defaultLanguage;
    }

    // Get labels and definitions.
    $query = "
      PREFIX skos:<http://www.w3.org/2004/02/skos/core#>

      SELECT *
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        <$uri> a skos:Concept.
        <$uri> skos:prefLabel ?prefLabel.
        OPTIONAL {
          <$uri> skos:altLabel ?altLabel FILTER(lang(?altLabel) = '$language').
        }
        OPTIONAL {
          <$uri> skos:definition ?definition FILTER(lang(?definition) = '$language').
        }
      }";

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return NULL;
    }

    // Concept not found (e.g. false concept URI).
    if (count($rows) == 0) {
      return NULL;
    }

    $pref_labels = array();
    $definitions = array('internal' => array(), 'external' => array());
    $alt_labels = array();
    $pref_label_default = '';
    foreach ($rows as $data) {
      if ($data->prefLabel->getLang() == $language) {
        $pref_label = $data->prefLabel->getValue();
      }
      else {
        $key = $data->prefLabel->getLang();
        $pref_labels[$key] = $data->prefLabel->getValue();
      }
      if ($data->prefLabel->getLang() == $this->defaultLanguage) {
        $pref_label_default = $data->prefLabel->getValue();
      }
      if (isset($data->definition)) {
        $definition = $this->clearDefinition($data->definition->getValue());
        if (!empty($definition)) {
          $definitions['internal'][] = $definition;
        }
      }
      if (isset($data->altLabel)) {
        $alt_labels[] = $data->altLabel->getValue();
      }
    }
    $definitions['internal'] = array_unique($definitions['internal']);

    // No data for given language.
    if (empty($pref_label)) {
      return (object) array(
        'uri' => $uri,
        'prefLabelDefault' => $pref_label_default,
        'language' => $this->defaultLanguage,
      );
    }

    // Get broader, narrower and related.
    $query = "
      PREFIX skos:<http://www.w3.org/2004/02/skos/core#>

      SELECT *
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        <$uri> a skos:Concept.
        OPTIONAL {
          <$uri> skos:broader ?broaderUri.
          ?broaderUri skos:prefLabel ?broader FILTER(lang(?broader) = '$language').
        }
        OPTIONAL {
          <$uri> skos:narrower ?narrowerUri.
          ?narrowerUri skos:prefLabel ?narrower FILTER(lang(?narrower) = '$language').
        }
        OPTIONAL {
          <$uri> skos:related ?relatedUri.
          ?relatedUri skos:prefLabel ?related FILTER(lang(?related) = '$language').
        }
      }";

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return NULL;
    }

    $broader    = array();
    $narrower    = array();
    $related    = array();
    foreach ($rows as $data) {
      if (isset($data->broaderUri)) {
        $broader[$data->broaderUri->getUri()] = array(
          'uri' => $data->broaderUri->getUri(),
          'prefLabel' => $data->broader->getValue(),
          'url' => $this->createUrl($data->broaderUri->getUri(), $data->broader->getValue(), $language),
        );
      }
      if (isset($data->narrowerUri)) {
        $narrower[$data->narrowerUri->getUri()] = array(
          'uri' => $data->narrowerUri->getUri(),
          'prefLabel' => $data->narrower->getValue(),
          'url' => $this->createUrl($data->narrowerUri->getUri(), $data->narrower->getValue(), $language),
        );
      }
      if (isset($data->relatedUri)) {
        $related[$data->relatedUri->getUri()] = array(
          'uri' => $data->relatedUri->getUri(),
          'prefLabel' => $data->related->getValue(),
          'url' => $this->createUrl($data->relatedUri->getUri(), $data->related->getValue(), $language),
        );
      }
    }

    $related_resources = \Drupal::moduleHandler()->invokeAll('smart_glossary_related_resource', array($this->glossaryStore, $uri, $language));
    $definitions['external'] = is_null($related_resources) ? array() : $related_resources;

    $concept = array(
      'uri' => $uri,
      'prefLabel' => $pref_label,
      'prefLabels' => $pref_labels,
      'altLabels' => array_unique($alt_labels),
      'definitions' => $definitions,
      'related' => array_values($related),
      'broader' => array_values($broader),
      'narrower' => array_values($narrower),
    );

    return (object) $concept;
  }

  /**
   * Update the a-z character list to be able to grey out unused letters.
   */
  public function updateCharacterList() {
    $chars = range('a', 'z');
    $advanced_settings = $this->config->get('advanced_settings');
    $char_a_z = isset($advanced_settings['char_a_z']) ? $advanced_settings['char_a_z'] : array();

    foreach ($this->languages as $language) {
      $char_a_z[$language] = array();
      foreach ($chars as $char) {
        $concepts = $this->getList($char, $language, 1);
        $char_a_z[$language][$char] = count($concepts);
      }
    }
    $advanced_settings['char_a_z'] = $char_a_z;
    $this->config->set('advanced_settings', $advanced_settings);
    $this->config->save();
  }

  /**
   * Get the number of concepts available for a specific language.
   *
   * @param string $language
   *   Optional; The language of the concepts to get
   *
   * @return int
   *   The number of concepts or NULL in case of an error
   */
  public function getNumberOfConcepts($language = '') {
    if ($this->glossaryStore === FALSE) {
      return NULL;
    }

    if (empty($language)) {
      $language = $this->defaultLanguage;
    }

    $query = "
      PREFIX skos:<http://www.w3.org/2004/02/skos/core#>

      SELECT DISTINCT ?concept
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        ?concept a skos:Concept.
        ?concept skos:prefLabel ?label FILTER(lang(?label) = '$language').
      }";

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return NULL;
    }

    return count($rows);
  }

  /**
   * Get the number of concept schemes available for a specific language.
   *
   * @param string $language
   *   Optional; The language of the concept schemes to get
   *
   * @return int
   *   The number of concept schemes or NULL in case of an error
   */
  public function getNumberOfConceptSchemes($language = '') {
    if ($this->glossaryStore === FALSE) {
      return NULL;
    }

    // @todo: maybe add a language check here?
    /*if (empty($language)) {
      $language = $this->defaultLanguage;
    }*/

    $query = "
      PREFIX skos:<http://www.w3.org/2004/02/skos/core#>

      SELECT DISTINCT ?conceptscheme
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        ?conceptscheme a skos:ConceptScheme.
      }";

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return NULL;
    }

    return count($rows);
  }

  /**
   * Get tha resource data from a given URI.
   *
   * @param string $uri
   *   The URI of the resource
   *
   * @return array
   *   The resource as an array or properties
   */
  public function getResource($uri) {
    if ($this->glossaryStore === FALSE) {
      return NULL;
    }

    $query = "
      SELECT ?property ?value
      " . (!empty($this->graphUri) ? 'FROM <' . $this->graphUri . '>' : '') . "
      WHERE {
        <$uri> ?property ?value.
      }";

    try {
      $rows = $this->glossaryStore->query($query);
    }
    catch (\Exception $e) {
      $this->error([$e->getMessage()]);
      return array();
    }

    $name_properties = array(
      'foaf:name',
      'skos:prefLabel',
      'dc:title',
      'geonames:name',
    );
    $result = array();
    foreach ($rows as $data) {
      $value = array();

      $property = array(
        'uri'  => $data->property->getUri(),
        'name' => $this->getPrefixes($data->property->getUri()));
      // Resource.
      if ($data->value instanceof EasyRdf_Resource) {
        $value['url'] = $data->value->getUri();
        $value['type'] = 'uri';
      }
      // Literal.
      else {
        $value['type'] = 'string';
        $value['value'] = $data->value->getValue();
        if (in_array($property['name'], $name_properties) && $data->value->getLang() == $this->defaultLanguage) {
          $result['name'] = $data->value->getValue();
        }
      }

      $result['resource'] = 'concept';
      $result['value'][] = array(
        'property' => $property,
        'value'  => $value,
      );
    }
    return $result;
  }

  /**
   * Get all the data for a specified URI for the Visual Mapper.
   *
   * @param string $root_uri
   *   The uri, which should be used as root.
   * @param string $lang
   *   The language of the selected concept.
   * @param boolean $fetch_relations
   *   TRUE if relations (broader, narrower, related) shell be fetched for the
   *   concept, FALSE if not.
   * @param boolean $parent_info
   *   If TRUE all the parent information for the root concept up to the concept
   *   schemes will be provided as a concept property name "parent_info".
   *
   * @return object
   *   The data to display in the VisualMapper
   */
  public function getVisualMapperData($root_uri = NULL, $lang = 'en', $fetch_relations = TRUE, $parent_info = FALSE) {
    // Get the data for the concept with relations.
    if ($fetch_relations) {
      $concept = $this->glossaryStore->getVisualMapperData($root_uri, $lang, $parent_info);
    }
    // Get the data for the concept without any relations.
    else {
      $concept = $this->glossaryStore->createRootUriObject($root_uri, $lang);
    }

    // Add the ID of the connection taxonomy-term, if a link to related content
    // has to be added.
    $advanced_settings = $this->config->getAdvancedSettings();
    if (isset($concept->id) && isset($advanced_settings['semantic_connection']) && isset($advanced_settings['semantic_connection']['add_show_content_link']) && $advanced_settings['semantic_connection']['add_show_content_link']) {
      /*$tid = db_select('field_data_field_uri', 'u')
        ->fields('u', array('entity_id'))
        ->condition('field_uri_value', $concept->id)
        ->execute()
        ->fetchField();*/

      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('field_data_field_uri', $concept->id)
        ->execute();

      if (!empty($tids)) {
        $concept->tid = reset($tids);
      }
    }

    if (!is_null($root_uri) && $concept->type != 'conceptScheme') {
      $pp_server_info = SemanticConnector::getSparqlConnectionDetails($this->config->getConnectionID());
      if ($pp_server_info !== FALSE) {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $language_mapping = $this->config->getLanguageMapping();
        $content_button_text = ((isset($language_mapping[$language]) && isset($language_mapping[$language]['wording']['showContentButton'])) ? $language_mapping[$language]['wording']['showContentButton'] : 'Show content');

        $themed_content = SemanticConnector::themeConcepts(array(array(
          'html' => $content_button_text,
          'uri' => $root_uri,
        )), $pp_server_info['pp_connection_id'], $pp_server_info['project_id'], '', array('smart_glossary_detail_page'));

        if ($themed_content != $content_button_text) {
          $concept->content_button = $themed_content;
        }
      }
    }

    return $concept;
  }

  /**
   * Create a nice URL for a concept.
   *
   * @param string $uri
   *   The URI of the concept
   * @param string $label
   *   The label(name) of the concept
   * @param string $glossary_language
   *   The language of the concept
   *
   * @return string
   *   The URL
   */
  protected function createUrl($uri, $label, $glossary_language) {
    global $base_url;

    $site_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $language_mapping = $this->config->getLanguageMapping();

    // Check if the glossary language is ok
    $mapping_exists = isset($language_mapping[$site_language]) && !empty($language_mapping[$site_language]['glossary_languages']);
    $glossary_languages = $mapping_exists ? $language_mapping[$site_language]['glossary_languages'] : array();
    if (!in_array($glossary_language, $glossary_languages)) {
      if (isset($language_mapping[$default_language]) && !empty($language_mapping[$default_language]['glossary_languages'])) {
        $glossary_language = $language_mapping[$default_language]['glossary_languages'][0];
      } else {
        $glossary_language = 'en';
      }
    }

    $url = $base_url . '/';
    // @todo: add language prefix.
    /*if (!empty($language->prefix)) {
      $url .= $language->prefix . '/';
    }*/
    $url .= $this->config->getBasePath() . '/' . $glossary_language . '/' . $label . '?uri=' . $uri;

    return $url;
  }

  /**
   * Replace special characters in names.
   *
   * @param string $name
   *   The name to clean
   *
   * @return string
   *   The cleaned name
   */
  protected function createName($name) {
    return trim($name);
    /*
    $search[0] = '/&([A-Za-z]{1,2})(tilde|grave|acute|circ|cedil|lig);/';
    $search[1] = '/&([A-Za-z]{1,2})(uml);/';
    $replace[0] = '$1';
    $replace[1] = '$1e';
    return preg_replace($search, $replace, htmlentities(trim($name)));
    */
  }

  /**
   * Rebuild a label.
   *
   * @param string $label
   *   The label
   *
   * @return string
   *   The rebuilt label
   */
  protected function rebuiltLabel($label) {
    return preg_replace('/ +/', ' ', trim($label));
  }

  /**
   * Converts a string to a slug, for use in URLs or CSS classes.
   *
   * This function properly replaces letters with accents with their
   * non-accented counterparts.
   *
   * @param string $string
   *   The string to convert.
   *
   * @return string
   *   The slug.
   */
  protected function stringToSlug($string) {
    $search[0] = '/&([A-Za-z]{1,2})(tilde|grave|acute|circ|cedil|lig);/';
    $search[1] = '/&([A-Za-z]{1,2})(uml);/';
    $replace[0] = '$1';
    $replace[1] = '$1e';
    $string = html_entity_decode(strtolower(preg_replace($search, $replace, htmlentities(trim($string)))));
    return preg_replace(
      array('/[^a-z0-9-]/', '/-+/', '/-$/'),
      array('-', '-', ''),
      $string
    );
  }

  /**
   * Trim a definition.
   *
   * @param string $definition
   *   The definition to trim
   *
   * @return string
   *   The trimmed definition
   */
  protected function clearDefinition($definition) {
    $definition = trim($definition);
    if (is_null($definition) || empty($definition)) {
      return '';
    }
    return $definition;
  }

  /**
   * Sort two concepts.
   *
   * @param array/string $a
   *   The first concept
   * @param array/string $b
   *   The second concept
   *
   * @return int
   *   The sort-value (positive or negative integer or 0)
   */
  protected function sortAlpha($a, $b) {
    if (is_array($a)) {
      return strcasecmp($a['label'], $b['label']);
    }
    return strcasecmp($a->prefLabel, $b->prefLabel);
  }

  /**
   * Replace URLs with prefixes in a property id.
   *
   * @param string $property
   *   The id of the property
   *
   * @return string
   *   The property-id with prefixes
   */
  protected function getPrefixes($property) {
    $search = array(
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'http://www.w3.org/2000/01/rdf-schema#',
      'http://purl.org/dc/elements/1.1/',
      'http://purl.org/dc/terms/',
      'http://dbpedia.org/property/',
      'http://xmlns.com/foaf/0.1/',
      'http://www.geonames.org/ontology#',
      'http://www.w3.org/2004/02/skos/core#',
      'http://www.w3.org/2002/07/owl#',
    );
    $replace = array(
      'rdf:',
      'rdfs:',
      'dc:',
      'dcterms:',
      'dbpedia:',
      'foaf:',
      'geonames:',
      'skos:',
      'owl:');
    return str_replace($search, $replace, $property);
  }

  /**
   * Log an error of "Smart Glossary" via watchdog.
   *
   * @param array|string $errors
   *   An array of errors
   * @param string $title
   *   The title of the error to be visible in the watchdog-log
   * @param int $severity
   *   The id of the watchdog-severity
   */
  protected function error($errors, $title = 'Glossary store', $severity = \Drupal\Core\Logger\RfcLogLevel::ERROR) {
    \Drupal::logger('smart_glossary')->log($severity, '%title: <pre>%errors</pre>', array('%title' => $title, '%errors' => $errors));
  }

  /**
   * Set the current language session.
   *
   * @param string $glossary_language
   *   Language-iso-code
   */
  public function setLanguage($glossary_language = NULL) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $language_mapping = $this->config->getLanguageMapping();
    $mapping_exists = isset($language_mapping[$language]) && !empty($language_mapping[$language]['glossary_languages']);
    $glossary_languages = $mapping_exists ? $language_mapping[$language]['glossary_languages'] : array();

    if (is_null($glossary_language) || !in_array($glossary_language, $glossary_languages)) {
      $glossary_language = empty($glossary_languages) ? \Drupal::languageManager()->getDefaultLanguage()->getId() : $glossary_languages[0];
    }

    $_SESSION['smart_glossary_language'][$this->config->id()] = $glossary_language;
  }

  /**
   * Theme function of the glossary header.
   *
   * @param string $area
   *   The area of the Smart Glossary to theme, possible values are:
   *   - "header"
   *   - "start"
   *   - "list"
   *   - "details"
   * @param array $variables
   *   Array of variables in the theme
   *
   * @return array
   *   Renderable arary of the area to theme.
   */
  public function themeGlossaryArea($area, $variables = array()) {
    $output = array();
    switch ($area) {
      case "header":
        global $base_url;

        // Get possible themes from other module's hooks.
        $themes = \Drupal::moduleHandler()->invokeAll('smart_glossary_header_theme');
        $language = $this->getCurrentLanguage($variables);

        if (!empty($themes)) {
          $theme = reset($themes);
        }
        else {
          $theme = 'smart_glossary_header';

          // Add javascript libraries and javascript settings.
          $output['#attached']['drupalSettings']['smart_glossary'] = array(
            'id' => $this->config->id(),
            'module_path' => $base_url . '/' . drupal_get_path('module', 'smart_glossary') . '/',
            'glossary_path' => $this->config->getBasePath(),
          );

          // Add visual mapper if available.
          if (SemanticConnector::visualMapperExists()) {
            $output['#attached']['library'][] = 'semantic_connector/visual_mapper';
          }
        }

        $advanced_settings = $this->config->getAdvancedSettings();
        if (!isset($advanced_settings['char_a_z']) || empty($advanced_settings['char_a_z'])) {
          $this->updateCharacterList();
          // Get the updated advanced settings.
          $advanced_settings = $this->config->getAdvancedSettings();
        }
        $char_a_z = $advanced_settings['char_a_z'];
        $char_a_z = isset($char_a_z[$language]) ? $char_a_z[$language] : array();

        if (empty($char_a_z)) {
          $chars = range('a', 'z');
          foreach ($chars as $char) {
            $char_a_z[$char] = 0;
          }
        }

        $output = array_merge($output, array(
          '#theme' => $theme,
          '#glossary_id' => $this->config->id(),
          '#glossary_path' => $this->config->getBasePath(),
          '#character_list' => $char_a_z,
          '#current_language' => $language,
        ));

        break;
      case "start":
        // Get possible themes from other module's hooks.
        $themes = \Drupal::moduleHandler()->invokeAll('smart_glossary_start_theme');
        $current_language = $this->getCurrentLanguage($variables);

        $visual_mapper_settings = $this->config->getVisualMapperSettings();
        $output = array(
          '#theme' => ((!empty($themes)) ? reset($themes) : 'smart_glossary_start'),
          '#glossary_id' => $this->config->id(),
          '#glossary_path' => $this->config->getBasePath(),
          '#visual_mapper_available' => SemanticConnector::visualMapperUsable() & (!isset($visual_mapper_settings['enabled']) || $visual_mapper_settings['enabled']),
          '#visual_mapper_settings' => $this->buildVisualMapperConfig(\Drupal::languageManager()->getCurrentLanguage()->getId()),
          '#current_language' => $current_language,
        );

        break;
      case "list":
        // Get possible themes from other module's hooks.
        $themes = \Drupal::moduleHandler()->invokeAll('smart_glossary_list_theme');

        $output = array(
          '#theme' => ((!empty($themes)) ? reset($themes) : 'smart_glossary_list'),
          '#list' => ((isset($variables['list'])) ? $variables['list'] : NULL),
        );

        break;
      case "details":
        $visual_mapper_settings = $this->config->getVisualMapperSettings();

        // Get possible themes from other module's hooks.
        $themes = \Drupal::moduleHandler()->invokeAll('smart_glossary_detail_view_theme');
        $current_language = $this->getCurrentLanguage($variables);
        $advanced_settings = $this->config->getAdvancedSettings();
        $rdf_url = $advanced_settings['add_rdf_link'] ? \Drupal::service('path.current')->getPath() . '.rdf?uri=' . $_GET['uri'] : '';
        $endpoint_url = $advanced_settings['add_endpoint_link'] ? $this->config->getConnection()->getUrl() : '';

        $output = array(
          '#theme' => ((!empty($themes)) ? reset($themes) : 'smart_glossary_detail'),
          '#glossary_id' => $this->config->id(),
          '#glossary_path' => $this->config->getBasePath(),
          '#term' => ((isset($variables['term'])) ? $variables['term'] : NULL),
          '#visual_mapper_available' => SemanticConnector::visualMapperUsable() & (!isset($visual_mapper_settings['enabled']) || $visual_mapper_settings['enabled']),
          '#visual_mapper_settings' => $this->buildVisualMapperConfig(\Drupal::languageManager()->getCurrentLanguage()->getId()),
          '#current_language' => $current_language,
          '#rdf_url' => $rdf_url,
          '#endpoint_url' => $endpoint_url,
        );

        break;
    }

    return $output;
  }

  /**
   * Build the JSON config required in the Visual Mapper.
   *
   * @param string $language
   *   The ISO code of the language to use.
   *
   * @return string
   *   The Visual Mapper config in a JSON format.
   */
  protected function buildVisualMapperConfig($language) {
    $language_mapping = $this->config->getLanguageMapping();
    $visual_mapper_config = array_merge($this->config->getVisualMapperSettings(), array('wording' => $language_mapping[$language]['wording']));

    // Add the relations.
    $visual_mapper_config['relations'] = array(
      'parents' => array(
        'colors' => array(
          'bright' => $visual_mapper_config['brightColors']['parent'],
          'dark' => $visual_mapper_config['darkColors']['parent'],
        ),
        'wording' => array(
          'legend' => $visual_mapper_config['wording']['legendParent'],
        ),
      ),
      'children' => array(
        'colors' => array(
          'bright' => $visual_mapper_config['brightColors']['children'],
          'dark' => $visual_mapper_config['darkColors']['children'],
        ),
        'wording' => array(
          'legend' => $visual_mapper_config['wording']['legendChildren'],
        ),
      ),
      'related' => array(
        'colors' => array(
          'bright' => $visual_mapper_config['brightColors']['related'],
          'dark' => $visual_mapper_config['darkColors']['related'],
        ),
        'wording' => array(
          'legend' => $visual_mapper_config['wording']['legendRelated'],
        ),
      ),
    );

    return json_encode($visual_mapper_config);
  }

  /**
   * Get an array of all available languages.
   *
   * @param SmartGlossaryConfig $config
   *   The Smart Glossary configuration object.
   *
   * @return array
   *   An array of language-iso-strings
   */
  public static function getAvailableLanguages($config = NULL) {
    if (is_null($config)) {
      $config = self::loadCurrentConfig();
    }
    $language_mapping = $config->getLanguageMapping();
    $languages = array();
    if (is_array($language_mapping) && !empty($language_mapping)) {

      foreach ($language_mapping as $mapping) {
        if (isset($mapping['glossary_languages'])) {
          $languages = array_merge($languages, $mapping['glossary_languages']);
        }
      }
    }

    return ((!empty($languages)) ? array_unique($languages) : array(\Drupal::languageManager()->getDefaultLanguage()->getId()));
  }

  /**
   * Find out the current language.
   *
   * @param array $variables
   *   Array of avaialble variables, that possibly includes a "language"-key
   *
   * @return string
   *   The iso-code of the current language
   */
  public function getCurrentLanguage($variables = array()) {
    // From the variables-array.
    if (isset($variables['current_language'])) {
      return $variables['current_language'];
    }
    // From the current browser-session.
    elseif (isset($_SESSION['smart_glossary_language']) && isset($_SESSION['smart_glossary_language'][$this->config->id()])) {
      return $_SESSION['smart_glossary_language'][$this->config->id()];
    }
    // From the first value of all available languages (default).
    else {
      return $this->languages[0];
    }
  }

  /**
   * Create a new PP GraphSearch configuration.
   *
   * @param string $title
   *   The title of the connection.
   * @param string $base_path
   *   The base path of the Smart Glossary configuration.
   * @param string $connection_id
   *   The ID of Semantic Connector connection
   * @param array $language_mapping
   *   The language mapping of the Smart Glossary configuration.
   * @param array $visual_mapper_settings
   *   Visual Mapper settings of the Smart Glossary configuration.
   * @param array $advanced_settings
   *   Advanced settings of the Smart Glossary configuration.
   *
   * @return SmartGlossaryConfig
   *   The Smart Glossary configuration.
   */
  public static function createConfiguration($title, $base_path, $connection_id, array $language_mapping = array(), array $visual_mapper_settings = array(), array $advanced_settings = array()) {
    $configuration = SmartGlossaryConfig::create();
    $configuration->set('id', SemanticConnector::createUniqueEntityMachineName('smart_glossary', $title));
    $configuration->setTitle($title);
    $configuration->setBasePath($base_path);
    $configuration->setLanguageMapping($language_mapping);
    $configuration->setVisualMapperSettings($visual_mapper_settings);
    $configuration->setAdvancedSettings($advanced_settings);
    $configuration->setConnectionId($connection_id);
    $configuration->save();

    return $configuration;
  }

  /**
   * Loads a Smart Glossary configuration from current path.
   *
   * @param string $current_path
   *   The current path
   *
   * @return object
   *   The Smart Glossary configuration object
   */
  public static function loadCurrentConfig($current_path = '') {
    static $config = NULL;

    if (is_null($config)) {
      if (empty($current_path)) {
        $current_path = \Drupal::service('path.current')->getPath();
        $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);

        if (!$path_alias) {
          $current_path = $path_alias;
        }
      }
      $current_path .= '/';
      $configs = SmartGlossaryConfig::loadMultiple();

      /** @var SmartGlossaryConfig $config */
      foreach ($configs as $config) {
        $base_path = $config->get('base_path') . '/';
        if (strpos($current_path, $base_path) === 0) {
          break;
        }
      }
    }

    return $config;
  }
}