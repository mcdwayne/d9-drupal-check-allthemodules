<?php

namespace Drupal\semantic_connector\Api;
use Drupal\semantic_connector\SemanticConnectorWatchdog;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_5_6
 *
 * API Class for the version 5.6
 */
class SemanticConnectorPPTApi_5_6 extends SemanticConnectorPPTApi_5_3 {

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
      'root' => $uri,
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
    ));

    return Json::decode($result);
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
    ));

    return Json::decode($result);
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
    ));
    $file_path = Json::decode($file_path);

    return (filter_var($file_path, FILTER_VALIDATE_URL) !== FALSE) ? $file_path : '';
  }

  /**
   * Get the corpora available for a PoolParty project.
   *
   * @param string $project_id
   *   The ID of the PP project to get the corpora for.
   *
   * @return array
   *   An array of associative corpus arrays containing following properties:
   *   - corpusId (string) --> Corpus id
   *   - corpusName (string) --> Corpus name
   *   - language (string) --> Language of corpus (en|de|es|fr|...)
   *   - upToDate (boolean) --> Up to date flag
   */
  public function getCorpora($project_id) {
    $corpora = array();

    $resource_path = $this->getApiPath() . 'corpusmanagement/' . $project_id . '/corpora';
    $result = $this->connection->get($resource_path);
    $corpus_data = Json::decode($result);
    if (!is_null($corpus_data) && isset($corpus_data['jsonCorpusList'])) {
      $corpora = $corpus_data['jsonCorpusList'];
    }

    return $corpora;
  }

  /**
   * Push text into a PoolParty corpus.
   *
   * @param string $project_id
   *   The ID of the PP project to use.
   * @param string $corpus_id
   *   The ID of the corpus to add the text to.
   * @param string $title
   *   The title of the document to push into the corpus.
   * @param mixed $data
   *   The text to push into the corpus.
   * @param string $data_type
   *   The type of the data. Can be one of the following values:
   *   - "text" for a text
   *   - "file" for a file object with a file ID
   *   - "file direct" for all other files without an ID
   * @param boolean $check_language
   *   Checks if the language of the uploaded file and the language of the
   *   corpus are the same.
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addDataToCorpus($project_id, $corpus_id, $title, $data, $data_type, $check_language = TRUE) {
    $resource_path = $this->getApiPath() . 'corpusmanagement/' . $project_id . '/add';
    $post_parameters = array(
      'corpusId' => $corpus_id,
      'checkLanguage' => $check_language,
    );

    $result = FALSE;
    switch ($data_type) {
      // Extract concepts from a given text.
      case 'text':
        $post_parameters = array_merge(array(
          'text' => $data,
          'title' => $title,
        ), $post_parameters);
        $result = $this->connection->post($resource_path, array(
          'data' => $post_parameters,
        ));
        break;

      // Extract concepts from a given file uploaded via file field.
      case 'file':
        // Check if the file is in the public folder
        // and the PoolParty GraphSearch server can read it.
        if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://')) {
          $public_path = $wrapper->realpath();
          $file_path = \Drupal::service('file_system')->realpath($data->getFileUri());
          if (strpos($file_path, $public_path) !== FALSE) {
            $post_parameters = array_merge(array(
              'file' => '@' . $file_path,
              'filename' => $title,
            ), $post_parameters);
            $result = $this->connection->post($resource_path, array(
              'data' => $post_parameters,
              'headers' => array('Content-Type' => 'multipart/form-data'),
            ));
          }
        }
        break;

      // Extract concepts from a given file
      case 'file direct':
        $post_parameters = array_merge(array(
          'file' => '@' . $data->file_path,
          'filename' => $title,
        ), $post_parameters);
        $result = $this->connection->post($resource_path, array(
          'data' => $post_parameters,
          'headers' => array('Content-Type' => 'multipart/form-data'),
        ));
        break;

      default:
        SemanticConnectorWatchdog::message('PPT API', 'The type of the data to push content into a corpus is not supported.');
        break;
    }

    return $result;
  }

  /**
   * Get the metadata (additional information) of a corpus.
   *
   * @param string $project_id
   *   The ID of the PP project to get the corpus metadata for.
   * @param string $corpus_id
   *   The ID of the corpus to get the metadata for.
   *
   * @return array
   *   An array of associative corpus arrays containing following properties:
   *   - corpusName (string) --> Corpus name
   *   - corpusId (string) --> Corpus id
   *   - language (string) --> Language of corpus (en|de|es|fr|...)
   *   - createdBy (string) --> Corpus created by user
   *   - created (string) --> Time of creation of the corpus
   *   - extractedTerms (int) --> Number of unique free terms
   *   - concepts (int) --> Number of unique concepts
   *   - suggestedTermOccurrences (int) --> Number of free terms
   *   - conceptOccurrences (int) --> Number of concepts
   *   - quality (string) --> Quality of the corpus
   *     Possible values are "good", "moderate" and "poor"
   *   - overallFileSize (string) --> Overall file size of documents in the corpus
   *   - lastModified (string) --> Last modification date of the corpus
   *   - storedDocuments (int) --> Number of stored documents in the corpus
   */
  public function getCorpusMetadata($project_id, $corpus_id) {
    $resource_path = $this->getApiPath() . 'corpusmanagement/' . $project_id . '/metadata';

    $get_parameters = array(
      'corpusId' => $corpus_id,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $corpus_data = Json::decode($result);

    return $corpus_data;
  }

  /**
   * Check if a corpus is up to date (or has to by analysed in case it is not).
   *
   * @param string $project_id
   *   The ID of the PP project of the corpus to check.
   * @param string $corpus_id
   *   The ID of the corpus to check.
   *
   * @return boolean
   *   TRUE if the corpus is up to date, FALSE if not
   */
  public function isCorpusUpToDate($project_id, $corpus_id) {
    $resource_path = $this->getApiPath() . 'corpusmanagement/' . $project_id . '/uptodate';

    $get_parameters = array(
      'corpusId' => $corpus_id,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $corpus_check = Json::decode($result);
    if (is_array($corpus_check) && isset($corpus_check['upToDate'])) {
      return $corpus_check['upToDate'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Start the analysis of a corpus.
   *
   * @param string $project_id
   *   The ID of the PP project of the corpus.
   * @param string $corpus_id
   *   The ID of the corpus to start the analysis for.
   *
   * @return array
   *   An associative array informing about the success of the analysis
   *   containing following keys:
   *   - success (bool) --> TRUE if the analysis worked, FALSE if not
   *   - message (string) --> This property is optional, but if it exists it
   *       includes more details about why the connection could not be
   *       established.
   *   - since PP 6.0 also "plainMessage" and "reportable"
   */
  public function analyzeCorpus($project_id, $corpus_id) {
    $analysis_info = array(
      'success' => FALSE,
      'message' => '',
    );

    $resource_path = $this->getApiPath() . 'corpusmanagement/' . $project_id . '/analyze';

    $post_parameters = array(
      'corpusId' => $corpus_id,
    );
    $variables = array(
      'data' => $post_parameters,
      'timeout' => 600 // Allowing up to 10 minutes for the process.
    );
    $result = $this->connection->post($resource_path, $variables);

    $result = Json::decode($result);
    if (is_array($result)) {
      $analysis_info = $result;
    }

    return $analysis_info;
  }

  /**
   * Suggest a set of concepts.
   *
   * @param string $project_id
   *   The ID of the project to suggest the concept for.
   * @param array $concepts
   *   An array of information about the concept to suggest. Following keys are
   *   supported:
   *   - prefLabels (Array of LanguageLiteral) --> Suggested preferred labels of
   *     the new concept - at least one must be in the project default language
   *   - broaderConcept (Array of IRI) --> Suggested broader concepts of the new
   *     concept. Optional
   *   - checkForDuplicates (boolean) --> If another Concept with the same
   *     preferred label already exists, an error will be thrown. (Default: true)
   *   - definition (Array of LanguageLiteral) --> Textual definitions of the
   *     new concept. Optional
   *   - note (String) --> Notes describing the new concept. Optional
   *   - relatedConcept (Array of IRI) --> Suggested related concepts of the new
   *     concept. Optional
   *   - score (float) --> Custom score for the new concept between 0 and 1.
   *     Optional
   *   - suffix (String) --> Suffix for manual uri creation. Optional
   *   - topConceptOf (Array of IRI) --> Schemes of which the suggested concept
   *     is a top concept. Optional
   *
   * @return array|bool
   *   An array or concept-arrays containing a uri-key or FALSE in case of an error.
   */
  public function suggestConcepts($project_id, array $concepts) {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/suggestConcepts';

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode(array(
        'conceptSuggestRequests' => $concepts,
      )),
    ));

    return Json::decode($result);
  }

  /**
   * Get the suggested concepts for a PoolParty project.
   *
   * @param string $project_id
   *   The ID of the project to get the suggested concepts for.
   * @param int $offset
   *   Start index from where the suggested concepts results should be returned.
   * @param int $number
   *   The number of retrieved suggested concepts. Use 0 to get all suggested
   *   concepts.
   *
   * @return array
   *   An array of suggested concept arrays containing following keys:
   *   - broaderConcepts (Array of IRI) --> Broader Concepts of the Suggested Concept
   *   - date (String) --> Creation date of the Suggested Concept
   *   - definitions (Array of Literal) --> Definitions of the Suggested Concept
   *   - note (String) --> Note of the Suggested Concept
   *   - prefLabels (Array of Literal) --> Preferred Labels URI of the Suggested Concept
   *   - relatedConcepts (Array of IRI) --> Related Concepts of the Suggested Concept
   *   - score (float) --> Score of the Suggested Concept
   *   - topConceptOf (Array of IRI) --> Schemes in which the Suggested Concept is a top concept.
   *   - uri (IRI) --> URI of the Suggested Concept
   */
  public function getSuggestedConcepts($project_id, $offset = 0, $number = 0) {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/suggestedConcepts';

    $get_parameters = [];
    if ($offset > 0) {
      $get_parameters['offset'] = $offset;
    }
    if ($number > 0) {
      $get_parameters['noOfConcepts'] = $number;
    }

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));

    return Json::decode($result);
  }
}