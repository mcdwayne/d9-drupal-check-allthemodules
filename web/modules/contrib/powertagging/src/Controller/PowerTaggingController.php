<?php
/**
 * @file
 * Contains \Drupal\powertagging\Controller\PowerTaggingController class.
 */

namespace Drupal\powertagging\Controller;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\PowerTagging;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for the PoolParty PowerTagging module.
 */
class PowerTaggingController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Returns concepts for the tag autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param PowerTaggingConfig $powertagging_config
   *   The PowerTagging configuration.
   * @param string $langcode
   *   The language of the entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocompleteTags(Request $request, PowerTaggingConfig $powertagging_config, $langcode) {
    $terms = [];
    if ($string = $request->query->get('term')) {
      $powertagging = new PowerTagging($powertagging_config);
      $powertagging->suggest($string, $langcode);
      $suggested_concepts = $powertagging->getResult();
      foreach ($suggested_concepts as $concept) {
        $terms[] = array(
          'tid' => $concept['tid'],
          'uri' => $concept['uri'],
          'name' => $concept['prefLabel'],
          'value' => $concept['prefLabel'],
          'matching_label' => isset($concept['matchingLabel']) ? $concept['matchingLabel'] : '',
          'context' => isset($concept['conceptSchemes']) && !empty($concept['conceptSchemes']) ? $concept['conceptSchemes'][0]['title'] : '',
          'type' => 'concept',
        );
      }
    }
    usort($terms, [$this, 'sortAutocompleteTags']);

    return new JsonResponse($terms);
  }

  /**
   * Function for extracting concepts and free terms from the content.
   *
   * @param PowerTaggingConfig $powertagging_config
   */
  public function extract($powertagging_config) {
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $files = isset($_POST['files']) ? $_POST['files'] : [];
    $entities = !empty($_POST['entities']) ? $_POST['entities'] : array();
    $settings = $_POST['settings'];
    $tags = array();

    // Remove line breaks and HTML tags from the content and convert HTML
    // characters to normal ones.
    $content = html_entity_decode(str_replace(array("\r", "\n", "\t"), "", strip_tags($content)), ENT_COMPAT, 'UTF-8');

    try {
      $powertagging = new PowerTagging($powertagging_config);
      $powertagging->extract($content, $files, $entities, $settings);
      $tags = $powertagging->getResult();

      if (empty($tags['messages']) && empty($tags['suggestion']['concepts']) && empty($tags['suggestion']['freeterms'])) {
        $tags['messages'][] = array(
          'type' => 'warning',
          'message' => t('No concepts or freeterms could be extracted from the entity\'s content.'),
        );
      }
    }
    catch (\Exception $e) {
      $tags['suggestion'] = array();
      $tags['messages'][] = array(
        'type' => 'error',
        'message' => t('Error while extracting tags.') . ' ' . $e->getMessage(),
      );
    }

    echo Json::encode($tags);
    exit();
  }

  /**
   * Callback function to sort the selected tags.
   */
  protected function sortAutocompleteTags($a, $b) {
    return strcasecmp($a['name'], $b['name']);
  }

  /**
   * Callback function for getting URIs of concepts.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the concepts with term IDs.
   */
  public function getConceptIDs() {
    $concepts = !empty($_POST['concepts']) ? $_POST['concepts'] : array();
    $settings = !empty($_POST['settings']) ? $_POST['settings'] : array();

    // Get the corresponding taxonomy term id.
    PowerTagging::addTermId($concepts, $settings['taxonomy_id'], 'concepts', $settings['entity_language']);

    return new JsonResponse($concepts);
  }

  /**
   * Get the data for the Visual Mapper inside a PowerTagging form.
   *
   * @param PowerTaggingConfig $powertagging_config
   *   The base-path to the glossary of choice.
   * @param boolean $fetch_relations
   *   TRUE if relations (broader, narrower, related) shell be fetched for the
   *   concept, FALSE if not.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the VisualMapper data.
   */
  public function getVisualMapperData($powertagging_config, $fetch_relations = TRUE) {
    $root_uri = isset($_GET['uri']) && !empty($_GET['uri']) ? $_GET['uri'] : NULL;
    $pt_settings = $powertagging_config->getConfig();
    $concept = new \stdClass();
    if (isset($_GET['lang']) && !empty($_GET['lang']) && isset($pt_settings['project']['languages'][$_GET['lang']]) && !empty($pt_settings['project']['languages'][$_GET['lang']])) {
      $lang = $pt_settings['project']['languages'][$_GET['lang']];

      // Get the data for the concept.
      /** @var \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $pp_server_connection */
      $pp_server_connection = $powertagging_config->getConnection();
      $sparql_endpoints = $pp_server_connection->getSparqlEndpoints($powertagging_config->getProjectId());
      $concept = NULL;
      if (count($sparql_endpoints) > 0) {
        /** @var \Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection $sparql_endpoint */
        $sparql_endpoint = reset($sparql_endpoints);
        /** @var \Drupal\semantic_connector\Api\SemanticConnectorSparqlApi $sparql_api */
        $sparql_api = $sparql_endpoint->getApi();

        if ($fetch_relations) {
          $concept = $sparql_api->getVisualMapperData($root_uri, $lang, (isset($_GET['parent_info']) && $_GET['parent_info']));
        }
        else {
          $concept = $sparql_api->createRootUriObject($root_uri, $lang);
        }
      }
    }

    return new JsonResponse($concept);
  }
}