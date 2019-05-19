<?php

/**
 * @file
 * The main class of the Smart Glossary DBpedia module.
 */

namespace Drupal\smart_glossary_dbpedia;
use Drupal\semantic_connector\Api\SemanticConnectorSparqlApi;

/**
 * A collection of static functions offered by the Smart Glossary DBpedia module.
 */
class SmartGlossaryDBpedia {
  /**
   * Get the content from dbpedia.
   *
   * @param string $dbpedia_domain
   *   The domain for the SPARQL endpoint.
   * @param array $dbpedia_uris
   *   An array with dbpedia URIs grouped by match type (close or exact match).
   * @param string $language
   *   The search language.
   *
   * @return array:
   *   array(
   *  'UNIQUE_NAME_1' => array(
   *      'title' => title for this dataset,
   *      'content' => array(
   *        0 => array(
   *          'label' => label for this definition (optional)
   *          'definition' => external definition (mandatory)
   *          'source' => name of the external source (optional)
   *          'url' => link to the external source (optional)
   *        ),
   *        1 => array(...)
   *      )
   *   ),
   *
   *   'UNIQUE_NAME_2' => array(...)
   *   )
   */
  public static function getDBpediaContents($dbpedia_domain, $dbpedia_uris, $language) {
    if (empty($dbpedia_uris)) {
      return array();
    }

    // Define SPARQL query template.
    $query = "
    PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
    PREFIX onto:<http://dbpedia.org/ontology/>
    PREFIX foaf:<http://xmlns.com/foaf/0.1/>

    SELECT *
    WHERE {
      <[URI]> rdfs:label ?label FILTER (lang(?label) = '$language').
      <[URI]> onto:abstract ?definition FILTER (lang(?definition) = '$language').
      <[URI]> foaf:isPrimaryTopicOf ?url .
    }";

    $dbpedia_store = new SemanticConnectorSparqlApi('http://' . $dbpedia_domain . '/sparql');

    // Go through the match property types [exactMatch | closeMatch]
    $result = array();
    foreach ($dbpedia_uris as $match_type => $uris) {
      // Go through all found dbpedia URIs and check if data is available.
      $uris = array_unique($uris);
      foreach ($uris as $uri) {
        try {
          $rows = $dbpedia_store->query(str_replace('[URI]', $uri, $query));
        }
        catch (\Exception $e) {
          \Drupal::logger('smart_glossary_dbpedia')->log(\Drupal\Core\Logger\RfcLogLevel::ERROR, 'Smart Glossary DBpedia: <pre>%errors</pre>', array('%errors' => $e->getMessage()));
          return array();
        }

        if ($rows->numRows()) {
          $row = $rows[0];
          $result[$match_type]['resources'][] = array(
            'uri' => $uri,
            'label' => $row->label->getValue(),
            'definition' => $row->definition->getValue(),
            'url' => $row->url->getUri(),
          );
        }
      }

      // If resources found for the dbpedia URIs then add the rest of data.
      if (isset($result[$match_type]['resources'])) {
        $result[$match_type]['source'] = t('Wikipedia');
        $result[$match_type]['title'] = ($match_type == 'exactMatch') ? t('Wikipedia definition:') : t('Wikipedia definition (similar term):');
      }
    }

    return $result;
  }
}