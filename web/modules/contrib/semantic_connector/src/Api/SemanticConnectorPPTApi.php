<?php

namespace Drupal\semantic_connector\Api;
use Drupal\semantic_connector\SemanticConnectorCurlConnection;

/**
 * Abstract Class SemanticConnectorPPTApi
 *
 * API Class for the PoolParty Thesaurus.
 */
abstract class SemanticConnectorPPTApi {
  protected $connection;
  protected $apiVersion;

  /**
   * The constructor of the PoolParty Thesaurus class.
   *
   * @param string $endpoint
   *   URL of the endpoint of the PoolParty-server.
   * @param string $credentials
   *   Username and password if required (format: "username:password").
   */
  public function __construct($endpoint, $credentials = '') {
    $this->connection = new SemanticConnectorCurlConnection($endpoint, $credentials);
    $this->apiVersion = str_replace(array('Drupal\semantic_connector\Api\SemanticConnectorPPTApi_', '_'), array('', '.'), get_class($this));
  }

  /**
   * Get the configured cURL-connection.
   *
   * @return SemanticConnectorCurlConnection
   *   The connection object.
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Get the configured used API version.
   *
   * @return string
   *   The API version.
   */
  public function getApiVersion() {
    return $this->apiVersion;
  }

  /**
   * Get the path to the PPT API.
   *
   * @return string
   *   The path to the PPT API.
   */
  public function getApiPath() {
    // Use API versioning for version 6.1+.
    return '/PoolParty/api/' . (version_compare($this->apiVersion, '6.1', '>=') ? $this->apiVersion . '/' : '');
  }

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
    return array('success' => FALSE);
  }

  /**
   * Get the version of the installed PoolParty web service.
   *
   * @return string
   *   The PoolParty version formatted like '4.1.6'
   */
  public function getVersion() {
    return '';
  }

  /**
   * Get a list of available projects of a PoolParty server.
   *
   * @return array
   *   A list of projects.
   */
  public function getProjects() {
    return [];
  }

  /**
   * Create a new project on a PoolParty server.
   *
   * @param string $title
   *   The title of the project.
   * @param string $language_default
   *   The default language of the project.
   * @param array $user_groups
   *   An arary of PoolParty user groups to make this project available for.
   * @param array $settings
   *   An associative array of optional settings. Possible values are:
   *   - author: (string) Project author
   *   - availableLanguages: (string[]) Additional languages
   *   - baseURL: (string) Base URL of the project
   *   - contributor: (string) Project contributor
   *   - description: (string) Project description
   *   - enableSkosXl: (bool) Enable SKOS-XL
   *   - enableWorkflow: (bool) Enable Workflow
   *   - idGeneration: (string) ID-Generation Method. Possible Values: increment, prefLabel, uuid, manual
   *   - incrementStart: (int) Increment start only needed when ID-Generation is 'increment'
   *   - license: (string) Project licensce. Must be a valid URI
   *   - projectIdentifier: (string) Project Identifier
   *   - publisher: (string) Project publisher
   *   - qualitySetting: (string) Quality Setting. Possible Values:default, autoIndexing, classification, documentsuggestion, restricted, disabled
   *   - repositoryType: (string) Repository Type. Possible Values: memory, native
   *   - snapshotInterval: (int) SnapshotInterval. Possible Values:5,10,15,30,-1:disabled
   *   - subject: (string) Primary subject of the project
   *   - workflowAssignee: (string) URI of the user that will get assigned to the workflow. Only used when workflow enabled
   *   - workflowState: (string) State of the workflow. Possible values: DRAFT, APPROVED
   *
   * @return string|bool
   *   A list of projects.
   */
  public function createProject($title, $language_default = 'en', $user_groups = array('Public'), $settings = []) {
    return FALSE;
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
    return '';
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
    return [];
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
  public function getTopConcepts($project_id, $scheme_uri, array $properties = [], $language = '') {
    return [];
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
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptScheme
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
  public function getConcepts($project_id, array $concept_uris, array $properties = [], $language = NULL) {
    return [];
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
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptScheme
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
  public function getConcept($project_id, $concept_uri, array $properties = [], $language = '') {
    return [];
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
    return '';
  }

  /**
   * Creates a new concept in a specific project.
   *
   * @param string $project_id
   *   The ID of the PoolParty project in which the concept should be created.
   * @param string $parent
   *   The URI of the parent concept or concept scheme of the new concept.
   * @param string $prefLabel
   *   The label in the default language of the project.
   *
   * @return string
   *   The URI of the new concept.
   */
  public function createConcept($project_id, $parent, $prefLabel) {
    return '';
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
    return '';
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
    return '';
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
    return '';
  }

  /**
   * Get all history items of a PoolParty project.
   *
   * @param string $project_id
   *   The ID of the project to get history items for.
   * @param int $from_time
   *   Optional; Only history items after this time will be included.
   * @param int $to_time
   *   Optional; Only history items before this time will be included.
   * @param string[] $events
   *   Optional; Filter by event type.
   *   Possible values: resourceChangeAddition, resourceChangeRemoval,
   *     resourceChangeUpdate, addRelation, removeRelation, addLiteral,
   *     removeLiteral, updateLiteral, addCollectionMember,
   *     removeCollectionMember, createCollection, deleteCollection,
   *     importConcept, resourceChangeAddition, addCustomAttributeLiteral,
   *     removeCustomAttributeLiteral ,updateCustomAttributeLiteral,
   *     addCustomRelation, removeCustomRelation, addCustomClass,
   *     removeCustomClass
   *
   * @return array
   *   An array of history items.
   */
  public function getHistory($project_id, $from_time = NULL, $to_time = NULL, $events = []) {
    return [];
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
  public function getSubTree($project_id, $uri, array $properties = [], $language = '') {
    return [];
  }

  /**
   * Returns a list of PoolParty user group names
   *
   * @return string[]
   *   Array of PoolParty user groups.
   */
  public function getUserGroups() {
    return [];
  }

  /**
   * Get the languages available in the PoolParty.
   *
   * @return array
   *   An associative array of available languages (iso-code --> label).
   */
  public function getLanguages() {
    return [];
  }

  /**
   * Get information about the extraction model for a PP project.
   *
   * @param string $project_id
   *   The ID of the PP project to get the extraction model info for.
   *
   * @return array|bool
   *   Associative array of extraction model info or FALSE in case of an error.
   *   Following keys are included:
   *   - lastBuildTime (string) --> Last extraction model build time
   *   - lastChangeTime (string) --> Last thesaurus change
   *   - upToDate (bool) --> Whether the extraction model is up-to-date or not
   */
  public function getExtractionModelInfo($project_id) {
    return FALSE;
  }

  /**
   * Refresh the extraction model for a PP project
   *
   * @param string $project_id
   *   The ID of the PP project to refresh the extraction model for.
   *
   * @return array
   *   An associative array informing about the success of the refreshing
   *   containing following keys:
   *   - success (bool) --> TRUE if the refreshing worked, FALSE if not
   *   - message (string) --> This property is optional, but if it exists it
   *       includes more details about why the connection could not be
   *       established.
   *   - since PP 6.0 also "plainMessage" and "reportable"
   */
  public function refreshExtractionModel($project_id) {
    return array('success' => FALSE);
  }

  /**
   * Get the corpora available for a PoolParty project.
   *
   * @param string $project_id
   *   The ID of the PP project to refresh the extraction model for.
   *
   * @return array
   *   An array of associative corpus arrays containing following properties:
   *   - corpusId (string) --> Corpus id
   *   - corpusName (string) --> Corpus name
   *   - language (string) --> Language of corpus (en|de|es|fr|...)
   *   - upToDate (boolean) --> Up to date flag
   */
  public function getCorpora($project_id) {
    return [];
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
    return '';
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
    return NULL;
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
    return FALSE;
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
    return array('success' => FALSE);
  }

  /**
   * Check if a corpus analysis is running for a project (only one analysis can
   * run per project at a time).
   *
   * @param string $project_id
   *   The ID of the PP project of the corpus to check.
   *
   * @return boolean
   *   TRUE if a corpus is running for that project, FALSE if not
   */
  public function isCorpusAnalysisRunning($project_id) {
    return FALSE;
  }

  /**
   * Returns the license of this PoolParty installation.
   *
   * @return array|boolean
   *   An associative array of information about the license or FALSE in case
   *   of an error. The array includes following keys:
   *   - expiryDate (String) --> e.g. "2049-12-31T00:00:00.000+01:00"
   *   - expiryDateInMillis (long) --> e.g. 2524518000000
   *   - features (Array of String) --> e.g. ["some feature", "other feature"]
   *   - ipConstraint (String) --> e.g. "*"
   *   - issueDate (String) --> e.g. "2018-01-01T00:00:00.000+01:00"
   *   - issueDateInMillis (long) --> e.g. 1514761200000
   *   - licensee (String) --> text describing the licensee
   *   - macConstraint (String) --> e.g. "*"
   *   - maximumVersion (String) --> "6.0.1"
   *   - minimumVersion (String) --> "7.0.1"
   *   - type (String) --> e.g. "https://semantic-web.com/api/type#15230"
   *   - uid (String) --> User ID of the license creator
   *   - valid (boolean) --> if the license is valid
   */
  public function getLicense() {
    return FALSE;
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
    return FALSE;
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
    return [];
  }

  /**
   * Get all classifiers for a specific PoolParty project.
   *
   * @param string $project_id
   *   The project UUID to get the classifiers for.
   *
   * @return array
   *   An array of classifiers, each one is an associative array including
   *   following keys:
   *   - isOnline (boolean) --> Online status
   *   - language (String) --> Language of classifier (en|de|es|fr|...)
   *   - name (String) --> Classifier name
   *   - status (String) --> Status
   *   - uri (String) --> Classifier id
   */
  public function getClassifiers($project_id) {
    return [];
  }
}