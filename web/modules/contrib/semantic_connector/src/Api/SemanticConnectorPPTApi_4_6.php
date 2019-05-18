<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_4_6
 *
 * API Class for the version 4.6
 */
class SemanticConnectorPPTApi_4_6 extends SemanticConnectorPPTApi {

  /**
   * This method checks if the PoolParty server exists and is running.
   *
   * @return array
   *   Associative array which following properties:
   *   - success (boolean): TRUE if a connection to the server can be
   *     established.
   *   - message (string): This property is optional, but if it exists it
   *     includes more details about why the connection could not be
   *     established.
   */
  public function available() {
    $is_available = array(
      'success' => FALSE,
      'message' => '',
    );
    $resource_path = $this->getApiPath() . 'version';
    $result = $this->connection->get($resource_path, array('headers' => array('Accept' => 'text/plain')));

    if (is_string($result) && !empty($result)) {
      $is_available['success'] = TRUE;
    }
    else {
      $is_available['message'] = 'PoolParty server is not available';
    }

    return $is_available;
  }

  /**
   * Get the version of the installed PoolParty web service.
   *
   * @return string
   *   The PoolParty version formatted like '4.6'
   */
  public function getVersion() {
    $resource_path = $this->getApiPath() . 'version';
    return $this->connection->get($resource_path, array('headers' => array('Accept' => 'text/plain')));
  }

  /**
   * Get a list of available projects of a PoolParty server.
   *
   * @return array
   *   A list of projects.
   */
  public function getProjects() {
    $resource_path = $this->getApiPath() . 'projects';
    $result = $this->connection->get($resource_path);
    $projects = Json::decode($result);

    if (is_array($projects)) {
      foreach ($projects as &$project) {
        if (isset($project['uriSupplement'])) {
          $project['sparql_endpoint_url'] = $this->connection->getEndpoint() . '/PoolParty/sparql/' . $project['uriSupplement'];
        }
        unset($project);
      }
    }
    else {
      $projects = array();
    }

    return $projects;
  }

  /**
   * Export data of a project as a file and store it on the server.
   *
   * @param string $project_id
   *   The ID of the PoolParty project to export and store.
   * @param string $format
   *   The returned RDF format.
   *   Possible values are: TriG, N3, Turtle, N-Triples, RDF/XML, TriX
   * @param string $export_modules
   *   A list of the export modules for the data that should be exported.
   *   Possible values are:
   *   - concepts - includes concept schemes, concepts, collections and notes
   *   - workflow - workflow status for all concepts
   *   - history - all history events
   *   - freeConcepts - all free concepts
   *   - void - the project VoiD graph
   *   - adms - the project ADMS graph
   *
   * @return string
   *   The URL of the stored file or an empty string if an error occurred.
   */
  public function storeProject($project_id, $format = 'RDF/XML', $export_modules = 'concepts') {
    $resource_path = $this->getApiPath() . 'projects/' . $project_id . '/store';
    $get_parameters = array(
      'format' => $format,
      'exportModules' => $export_modules,
    );
    $file_path = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
      'headers' => array('Accept' => 'text/plain'),
    ));

    return (filter_var($file_path, FILTER_VALIDATE_URL) !== FALSE) ? $file_path : '';
  }

  /**
   * Gets information about a concept scheme.
   *
   * @param string $project_id
   *   The ID of the PoolParty project of the concepts.
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   An array of associative concept scheme arrays within the respective
   *   PoolParty project.
   */
  public function getConceptSchemes($project_id, $language = '') {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/schemes';
    $get_parameters = array(
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept_schemes = Json::decode($result);

    return $concept_schemes;
  }

  /**
   * Gets a list of all top concepts of a specific concept scheme.
   *
   * @param string $project_id
   *   The ID of the PoolParty project.
   * @param string $scheme_uri
   *   The URI of the concept scheme.
   * @param array $properties
   *   A list of additional properties to fetch (e.g. skos:altLabel, skso:hiddenLabel).
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   A list of top concepts.
   */
  public function getTopConcepts($project_id, $scheme_uri, array $properties = array(), $language = '') {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/topconcepts';
    $get_parameters = array(
      'scheme' => $scheme_uri,
      'properties' => implode(',', $properties),
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept = Json::decode($result);

    return $concept;
  }

  /**
   * Get a list of all concepts under a specified concept in a tree format.
   *
   * @param int $project_id
   *   The ID of the PoolParty project.
   * @param string $uri
   *   A concept URI.
   * @param array $properties
   *   A list of additional properties to fetch (e.g. skos:altLabel, skso:hiddenLabel).
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   A list of concept objects in a tree format.
   */
  public function getSubTree($project_id, $uri, array $properties = array(), $language = '') {
    // PoolParty Thesaurus API Bug (version 5.3.1):
    // At least the prefLabel proberty must be indicated.
    if (!in_array('skos:prefLabel', $properties)) {
      $properties[] = 'skos:prefLabel';
    }

    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/subtree';
    $get_parameters = array(
      'uri' => $uri,
      'properties' => implode(',', $properties),
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
      'timeout' => 120 // Allowing up to 2 minutes for the process.
    ));
    $concept = Json::decode($result);

    return $concept;
  }

  /**
   * Get information about concepts.
   *
   * @param string $project_id
   *   The ID of the PoolParty project of the concepts.
   * @param array $concept_uris
   *   An array of concept URIs to get information for.
   * @param array $properties
   *   Array of additional concept properties that will be fetched (only
   *   properties uri and prefLabel are included by default). Possible values
   *   are:
   *   - skos:prefLabel
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptSchemes
   *   - all
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   Array of associative concept arrays within the respective PoolParty
   *   project with following keys:
   *   - uri --> URI of the concept
   *   - prefLabel --> Preferred label
   *   - altLabels --> Alternative labels
   *   - hiddenLabels --> Hidden labels
   *   - definitions --> Definitions
   *   - broaders --> Broader concepts
   *   - narrowers --> Narrower concepts
   *   - relateds --> Related concepts
   *   - conceptSchemes --> Concept schemes
   */
  public function getConcepts($project_id, array $concept_uris, array $properties = array(), $language = '') {
    if (empty($concept_uris)) {
      return array();
    }

    if (!in_array('skos:prefLabel', $properties)) {
      $properties[] = 'skos:prefLabel';
    }

    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/concepts';
    foreach ($concept_uris as $uri_count => $concept_uri) {
      if ($uri_count == 0) {
        $resource_path .= '?';
      }
      else {
        $resource_path .= '&';
      }
      $resource_path .= 'concepts=' . urlencode($concept_uri);
    }
    foreach ($properties as $property) {
      $resource_path .= '&properties=' . urlencode($property);
    }
    if (!is_null($language)) {
      $resource_path .= '&language=' . urlencode($language);
    }

    $result = $this->connection->get($resource_path);

    $concepts = Json::decode($result);
    return $concepts;
  }

  /**
   * Get information about a concept.
   *
   * @param string $project_id
   *   The ID of the PoolParty project of the concepts.
   * @param string $concept_uri
   *   The concept URI, from which the data should be retrieved.
   * @param string array $properties
   *   Array of additional concept properties that will be fetched (only
   *   properties uri and prefLabel are included by default). Possible values
   *   are:
   *   - skos:prefLabel
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptSchemes
   *   - all
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   An associative concept array within the respective PoolParty project with
   *   following keys:
   *   - uri --> URI of the concept
   *   - prefLabel --> Preferred label
   *   - altLabels --> Alternative labels
   *   - hiddenLabels --> Hidden labels
   *   - definitions --> Definitions
   *   - broaders --> Broader concepts
   *   - narrowers --> Narrower concepts
   *   - relateds --> Related concepts
   *   - conceptSchemes --> Concept schemes
   */
  public function getConcept($project_id, $concept_uri, array $properties = array(), $language = '') {
    // PoolParty Thesaurus API Bug (version 5.3.1):
    // At least the prefLabel proberty must be indicated.

    if (!in_array('skos:prefLabel', $properties)) {
      $properties[] = 'skos:prefLabel';
    }

    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/concept';
    $get_parameters = array(
      'concept' => $concept_uri,
      'properties' => implode(',', $properties),
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept = Json::decode($result);

    return $concept;
  }

  /**
   * Creates a concept scheme in a specific project.
   *
   * @param string $project_id
   *   The ID of the PoolParty project in which the concept scheme should be created.
   * @param string $title
   *   The title of the new concept scheme.
   * @param string $description
   *   A description for the new concept scheme.
   * @param string $creator
   *   The name of the creator of the new concept scheme.
   *
   * @return string
   *   The URI of the new concept scheme.
   */
  public function createConceptScheme($project_id, $title, $description, $creator = 'Drupal') {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/createConceptScheme';
    $post_parameters = array(
      'title' => $title,
      'description' => $description,
      'creator' => $creator,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
      'headers' => array('Accept' => 'text/plain'),
    ));

    return $result;
  }

  /**
   * Creates a new concept in a specific project.
   *
   * @param string $project_id
   *   The ID of the PoolParty project in which the concept should be created.
   * @param string $prefLabel
   *   The label in the default language of the project.
   * @param string $parent
   *   The URI of the parent concept or concept scheme of the new concept.
   *
   * @return string
   *   The URI of the new concept.
   */
  public function createConcept($project_id, $prefLabel, $parent) {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/createConcept';
    $post_parameters = array(
      'prefLabel' => $prefLabel,
      'parent' => $parent,
    );
    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
      'headers' => array('Accept' => 'text/plain'),
    ));

    return $result;
  }

  /**
   * Adds a SKOS relation between two existing concepts
   *
   * @param string $project_id
   *   The ID of the PoolParty project.
   * @param string $source
   *   The URI of the source concept.
   * @param string $target
   *   The URI of the target concept.
   * @param string $property
   *   The relation property. Possible values are:
   *   - broader
   *   - narrower
   *   - related
   *   - hasTopConcept
   *   - topConceptOf
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addRelation($project_id, $source, $target, $property = 'broader') {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/addRelation';
    $post_parameters = array(
      'sourceConcept' => $source,
      'targetConcept' => $target,
      'property' => $property,
    );
    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }

  /**
   * Adds a literal to an existing concept
   *
   * @param string $project_id
   *  The ID of the PoolParty project.
   * @param string $concept_uri
   *  The URI of the Concept.
   * @param string $property
   *  The SKOS property. Possible values are:
   *  - preferredLabel
   *  - alternativeLabel
   *  - hiddenLabel
   *  - definition
   *  - scopeNote
   *  - example
   *  - notation
   * @param string $label
   *  The RDF literal to add.
   * @param string $language
   *  The attribute language.
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addLiteral($project_id, $concept_uri, $property, $label, $language = NULL) {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/addLiteral';
    $post_parameters = array(
      'concept' => $concept_uri,
      'label' => $label,
      'property' => $property,
    );

    if (!is_null($language) && !empty($language)) {
      $post_parameters['language'] = $language;
    }

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }

  /**
   * Adds a literal to an existing concept
   *
   * @param string $project_id
   *  The ID of the PoolParty project.
   * @param string $concept_uri
   *  The URI of the Concept to add the property to.
   * @param string $attribute_uri
   *  The URI of the custom attribute property.
   * @param string $value
   *  The attribute value that should be added
   * @param string $language
   *  The attribute language.
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addCustomAttribute($project_id, $concept_uri, $attribute_uri, $value, $language = NULL) {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/addCustomAttribute';
    $post_parameters = array(
      'resource' => $concept_uri,
      'property' => $attribute_uri,
      'value' => $value,
    );

    if (!is_null($language) && !empty($language)) {
      $post_parameters['language'] = $language;
    }

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }

  /**
   * Get the languages available in the PoolParty.
   *
   * @return array
   *   An associative array of available languages (iso-code --> label).
   */
  public function getLanguages() {
    // Return a static list of all the PoolParty languages and their labels.
    $languages = array(
      'sq' => 'Albanian [sq]',
      'sq-AL' => 'Albanian (Albania) [sq-AL]',
      'ar' => 'Arabic [ar]',
      'ar-DZ' => 'Arabic (Algeria) [ar-DZ]',
      'ar-BH' => 'Arabic (Bahrain) [ar-BH]',
      'ar-EG' => 'Arabic (Egypt) [ar-EG]',
      'ar-IQ' => 'Arabic (Iraq) [ar-IQ]',
      'ar-JO' => 'Arabic (Jordan) [ar-JO]',
      'ar-KW' => 'Arabic (Kuwait) [ar-KW]',
      'ar-LB' => 'Arabic (Lebanon) [ar-LB]',
      'ar-LY' => 'Arabic (Libya) [ar-LY]',
      'ar-MA' => 'Arabic (Morocco) [ar-MA]',
      'ar-OM' => 'Arabic (Oman) [ar-OM]',
      'ar-QA' => 'Arabic (Qatar) [ar-QA]',
      'ar-SA' => 'Arabic (Saudi Arabia) [ar-SA]',
      'ar-SD' => 'Arabic (Sudan) [ar-SD]',
      'ar-SY' => 'Arabic (Syria) [ar-SY]',
      'ar-TN' => 'Arabic (Tunisia) [ar-TN]',
      'ar-AE' => 'Arabic (United Arab Emirates) [ar-AE]',
      'ar-YE' => 'Arabic (Yemen) [ar-YE]',
      'be' => 'Belarusian [be]',
      'be-BY' => 'Belarusian (Belarus) [be-BY]',
      'bg' => 'Bulgarian [bg]',
      'bg-BG' => 'Bulgarian (Bulgaria) [bg-BG]',
      'ca' => 'Catalan [ca]',
      'ca-ES' => 'Catalan (Spain) [ca-ES]',
      'zh' => 'Chinese [zh]',
      'zh-CN' => 'Chinese (China) [zh-CN]',
      'zh-HK' => 'Chinese (Hong Kong) [zh-HK]',
      'zh-SG' => 'Chinese (Singapore) [zh-SG]',
      'zh-TW' => 'Chinese (Taiwan) [zh-TW]',
      'hr' => 'Croatian [hr]',
      'hr-HR' => 'Croatian (Croatia) [hr-HR]',
      'cs' => 'Czech [cs]',
      'cs-CZ' => 'Czech (Czech Republic) [cs-CZ]',
      'da' => 'Danish [da]',
      'da-DK' => 'Danish (Denmark) [da-DK]',
      'nl' => 'Dutch [nl]',
      'nl-BE' => 'Dutch (Belgium) [nl-BE]',
      'nl-NL' => 'Dutch (Netherlands) [nl-NL]',
      'en' => 'English [en]',
      'en-AU' => 'English (Australia) [en-AU]',
      'en-CA' => 'English (Canada) [en-CA]',
      'en-IN' => 'English (India) [en-IN]',
      'en-IE' => 'English (Ireland) [en-IE]',
      'en-MT' => 'English (Malta) [en-MT]',
      'en-NZ' => 'English (New Zealand) [en-NZ]',
      'en-PH' => 'English (Philippines) [en-PH]',
      'en-SG' => 'English (Singapore) [en-SG]',
      'en-ZA' => 'English (South Africa) [en-ZA]',
      'en-GB' => 'English (United Kingdom) [en-GB]',
      'en-US' => 'English (United States) [en-US]',
      'et' => 'Estonian [et]',
      'et-EE' => 'Estonian (Estonia) [et-EE]',
      'fi' => 'Finnish [fi]',
      'fi-FI' => 'Finnish (Finland) [fi-FI]',
      'fr' => 'French [fr]',
      'fr-BE' => 'French (Belgium) [fr-BE]',
      'fr-CA' => 'French (Canada) [fr-CA]',
      'fr-FR' => 'French (France) [fr-FR]',
      'fr-LU' => 'French (Luxembourg) [fr-LU]',
      'fr-CH' => 'French (Switzerland) [fr-CH]',
      'de' => 'German [de]',
      'de-AT' => 'German (Austria) [de-AT]',
      'de-DE' => 'German (Germany) [de-DE]',
      'de-GR' => 'German (Greece) [de-GR]',
      'de-LU' => 'German (Luxembourg) [de-LU]',
      'de-CH' => 'German (Switzerland) [de-CH]',
      'el' => 'Greek [el]',
      'el-CY' => 'Greek (Cyprus) [el-CY]',
      'el-GR' => 'Greek (Greece) [el-GR]',
      'he' => 'Hebrew [he]',
      'he-IL' => 'Hebrew (Israel) [he-IL]',
      'hi' => 'Hindi [hi]',
      'hi-IN' => 'Hindi (India) [hi-IN]',
      'hu' => 'Hungarian [hu]',
      'hu-HU' => 'Hungarian (Hungary) [hu-HU]',
      'is' => 'Icelandic [is]',
      'is-IS' => 'Icelandic (Iceland) [is-IS]',
      'id' => 'Indonesian [id]',
      'id-ID' => 'Indonesian (Indonesia) [id-ID]',
      'ga' => 'Irish [ga]',
      'ga-IE' => 'Irish (Ireland) [ga-IE]',
      'it' => 'Italian [it]',
      'it-IT' => 'Italian (Italy) [it-IT]',
      'it-CH' => 'Italian (Switzerland) [it-CH]',
      'ja' => 'Japanese [ja]',
      'ja-JP' => 'Japanese (Japan) [ja-JP]',
      'ja-JP-u-ca-japanese-x-lvariant-JP' => 'Japanese (Japan,JP) [ja-JP-u-ca-japanese-x-lvariant-JP]',
      'ko' => 'Korean [ko]',
      'ko-KR' => 'Korean (South Korea) [ko-KR]',
      'lv' => 'Latvian [lv]',
      'lv-LV' => 'Latvian (Latvia) [lv-LV]',
      'lt' => 'Lithuanian [lt]',
      'lt-LT' => 'Lithuanian (Lithuania) [lt-LT]',
      'mk' => 'Macedonian [mk]',
      'mk-MK' => 'Macedonian (Macedonia) [mk-MK]',
      'ms' => 'Malay [ms]',
      'ms-MY' => 'Malay (Malaysia) [ms-MY]',
      'mt' => 'Maltese [mt]',
      'mt-MT' => 'Maltese (Malta) [mt-MT]',
      'no' => 'Norwegian [no]',
      'no-NO' => 'Norwegian (Norway) [no-NO]',
      'nn-NO' => 'Norwegian (Norway,Nynorsk) [nn-NO]',
      'pl' => 'Polish [pl]',
      'pl-PL' => 'Polish (Poland) [pl-PL]',
      'pt' => 'Portuguese [pt]',
      'pt-BR' => 'Portuguese (Brazil) [pt-BR]',
      'pt-PT' => 'Portuguese (Portugal) [pt-PT]',
      'ro' => 'Romanian [ro]',
      'ro-RO' => 'Romanian (Romania) [ro-RO]',
      'ru' => 'Russian [ru]',
      'ru-RU' => 'Russian (Russia) [ru-RU]',
      'sr' => 'Serbian [sr]',
      'sr-BA' => 'Serbian (Bosnia and Herzegovina) [sr-BA]',
      'sr-Latn' => 'Serbian (Latin) [sr-Latn]',
      'sr-Latn-BA' => 'Serbian (Latin,Bosnia and Herzegovina) [sr-Latn-BA]',
      'sr-Latn-ME' => 'Serbian (Latin,Montenegro) [sr-Latn-ME]',
      'sr-Latn-RS' => 'Serbian (Latin,Serbia) [sr-Latn-RS]',
      'sr-ME' => 'Serbian (Montenegro) [sr-ME]',
      'sr-CS' => 'Serbian (Serbia and Montenegro) [sr-CS]',
      'sr-RS' => 'Serbian (Serbia) [sr-RS]',
      'sk' => 'Slovak [sk]',
      'sk-SK' => 'Slovak (Slovakia) [sk-SK]',
      'sl' => 'Slovenian [sl]',
      'sl-SI' => 'Slovenian (Slovenia) [sl-SI]',
      'es' => 'Spanish [es]',
      'es-AR' => 'Spanish (Argentina) [es-AR]',
      'es-BO' => 'Spanish (Bolivia) [es-BO]',
      'es-CL' => 'Spanish (Chile) [es-CL]',
      'es-CO' => 'Spanish (Colombia) [es-CO]',
      'es-CR' => 'Spanish (Costa Rica) [es-CR]',
      'es-CU' => 'Spanish (Cuba) [es-CU]',
      'es-DO' => 'Spanish (Dominican Republic) [es-DO]',
      'es-EC' => 'Spanish (Ecuador) [es-EC]',
      'es-SV' => 'Spanish (El Salvador) [es-SV]',
      'es-GT' => 'Spanish (Guatemala) [es-GT]',
      'es-HN' => 'Spanish (Honduras) [es-HN]',
      'es-MX' => 'Spanish (Mexico) [es-MX]',
      'es-NI' => 'Spanish (Nicaragua) [es-NI]',
      'es-PA' => 'Spanish (Panama) [es-PA]',
      'es-PY' => 'Spanish (Paraguay) [es-PY]',
      'es-PE' => 'Spanish (Peru) [es-PE]',
      'es-PR' => 'Spanish (Puerto Rico) [es-PR]',
      'es-ES' => 'Spanish (Spain) [es-ES]',
      'es-US' => 'Spanish (United States) [es-US]',
      'es-UY' => 'Spanish (Uruguay) [es-UY]',
      'es-VE' => 'Spanish (Venezuela) [es-VE]',
      'sv' => 'Swedish [sv]',
      'sv-SE' => 'Swedish (Sweden) [sv-SE]',
      'th' => 'Thai [th]',
      'th-TH' => 'Thai (Thailand) [th-TH]',
      'th-TH-u-nu-thai-x-lvariant-TH' => 'Thai (Thailand,TH) [th-TH-u-nu-thai-x-lvariant-TH]',
      'tr' => 'Turkish [tr]',
      'tr-TR' => 'Turkish (Turkey) [tr-TR]',
      'uk' => 'Ukrainian [uk]',
      'uk-UA' => 'Ukrainian (Ukraine) [uk-UA]',
      'vi' => 'Vietnamese [vi]',
      'vi-VN' => 'Vietnamese (Vietnam) [vi-VN]',
    );
    return $languages;
  }
}
