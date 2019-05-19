<?php

/**
 * @file Contains \Drupal\smart_glossary\Entity\SmartGlossary.
 */

namespace Drupal\smart_glossary\Entity;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection;
use Drupal\semantic_connector\SemanticConnector;

/**
 * @ConfigEntityType(
 *   id ="smart_glossary",
 *   label = @Translation("Smart Glossary"),
 *   handlers = {
 *     "list_builder" = "Drupal\smart_glossary\SmartGlossaryConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\smart_glossary\Form\SmartGlossaryConfigForm",
 *       "add" = "Drupal\smart_glossary\Form\SmartGlossaryConfigForm",
 *       "edit" = "Drupal\smart_glossary\Form\SmartGlossaryConfigForm",
 *       "delete" = "Drupal\smart_glossary\Form\SmartGlossaryConfigDeleteForm",
 *       "clone" = "Drupal\smart_glossary\Form\SmartGlossaryConfigCloneForm"
 *     }
 *   },
 *   config_prefix = "smart_glossary",
 *   admin_permission = "administer smart glossary",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/smart-glossary/configurations/{smart_glossary}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/smart-glossary/configurations/{smart_glossary}",
 *     "collection" = "/admin/config/semantic-drupal/smart-glossary/",
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "connection_id",
 *     "base_path",
 *     "language_mapping",
 *     "visual_mapper_settings",
 *     "advanced_settings",
 *   }
 * )
 */
class SmartGlossaryConfig extends ConfigEntityBase implements SmartGlossaryConfigInterface {
  protected $id;
  protected $title;
  protected $connection_id;
  protected $base_path;
  protected $language_mapping;
  protected $visual_mapper_settings;
  protected $advanced_settings;
  /** @var SemanticConnectorSparqlEndpointConnection $connection */
  protected $connection;

  /**
   * The constructor of the SemanticConnectorPPServerConnection class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (is_null($this->id())) {
      $this->connection_id = 0;
      $this->base_path = 'glossary';
      $this->language_mapping = array();
      $this->visual_mapper_settings = array();
      $this->advanced_settings = array();
    }

    $this->connection = SemanticConnector::getConnection('sparql_endpoint', $this->connection_id);

    // Merge the settings with the default ones.
    $this->visual_mapper_settings = $this->visual_mapper_settings + $this->getDefaultVisualMapperSettings();
    $this->advanced_settings = $this->advanced_settings + $this->getDefaultAdvancedSettings();

    // Make sure that the language mapping is complete and available for every
    // language.
    $installed_languages = \Drupal::languageManager()->getLanguages();
    /** @var \Drupal\Core\Language\LanguageInterface $installed_language */
    foreach ($installed_languages as $installed_language) {
      $lang_code = $installed_language->getId();
      if (!isset($this->language_mapping[$lang_code])) {
        $this->language_mapping[$lang_code] = array(
          'glossary_languages' => array($lang_code),
        );
      }
      // Merge the mapping with the default one.
      $this->language_mapping[$lang_code] = $this->language_mapping[$lang_code] + $this->getDefaultLanguageMapping();
    }
  }

  /**
   * {@inheritdoc|}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConnectionID() {
    return $this->connection_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function getBasePath() {
    return $this->base_path;
  }

  /**
   * {@inheritdoc|}
   */
  public function getLanguageMapping() {
    return $this->language_mapping;
  }

  /**
   * {@inheritdoc|}
   */
  public function getVisualMapperSettings() {
    return $this->visual_mapper_settings;
  }

  /**
   * {@inheritdoc|}
   */
  public function getAdvancedSettings() {
    return $this->advanced_settings;
  }

  /**
   * Get the default configuration for the advanced settings.
   *
   * @return array
   *   The configuration array.
   */
  private function getDefaultLanguageMapping() {
    return array(
      'wording' => array(
        'glossaryRoot' => 'Home',
        'homeButton' => 'Home',
        'showDefinitionButton' => 'Show definition',
        'noDefinition' => 'No definition',
        'showContentButton' => 'Show content',
        'legendConceptScheme' => 'Concept Scheme',
        'legendParent' => 'Broader',
        'legendChildren' => 'Narrower',
        'legendRelated' => 'Related',
        'currentConcept' => 'Current concept',
        'helpButton' => 'Help',
        'helpText' => array(
          'value' => '
        <h2 id="helptext-about-the-concepts">About the concepts</h2>
        <p>The fundamental element of the SKOS vocabulary is the concept. Concepts are the units of thought &mdash;ideas, meanings, or (categories of) objects and events. We can add labels to our concept. There are two types of labels: Preferred Labels (<a class="external-link" href="http://www.w3.org/TR/skos-reference/#prefLabel" rel="nofollow">skos:prefLabel</a>) and Alternative Labels (<a class="external-link" href="http://www.w3.org/TR/skos-reference/#altLabel" rel="nofollow">skos:altLabel</a>). Note that a concept can only have one preferred label per language but it can have many alternative labels. Now we can start to add semantic relationships. SKOS offers: hierarchical, associative and mapping relationships. The hierarchical relationships are <a class="external-link" href="http://www.w3.org/TR/skos-reference/#broader" rel="nofollow">skos:broader</a> and <a class="external-link" href="http://www.w3.org/TR/skos-reference/#narrower" rel="nofollow">skos:narrower</a>. For example, the concept &ldquo;Computer&rdquo; is <span class="color-broader"><strong>broader</strong></span> than the concept &ldquo;Laptop.&rdquo; Likewise, the concept &ldquo;Laptop&rdquo; is <strong><span class="color-narrower">narrower</span></strong> than the concept &rdquo;Computer.&rdquo; The basic tree structure of the SKOS Glossary is build with this two relationships, forming roots and branches in several levels. SKOS has also one associative relationship, <a class="external-link" href="http://www.w3.org/TR/skos-reference/#related" rel="nofollow">skos:related</a>, which is used to assert a relationship between two concepts. For example, the concept &ldquo;Computer&rdquo; is <span class="color-related"><strong>related</strong></span> to the concept &ldquo;Software&rdquo;. Using this relationship type interconnections outside the hierarchical structure of the glossary can be realized.</p>
        <h2 id="helptext-functions-of-the-Browser">Functions of the Browser</h2>
        <p><strong>Glossary root</strong></p>
        <p>In order to provide an efficient access to the entry points of broader/narrower concept hierarchies, SKOS defines a <code><a href="http://www.w3.org/TR/skos-reference#hasTopConcept" class="external-link" rel="nofollow">skos:hasTopConcept</a></code>property. User can view all those Top concepts at once, to get an overview of all possible branches of the glossary, in pressing the top left button &quot;<strong>Glossary Root</strong>&quot;.</p>
        <p><strong>Definition</strong></p>
        <p>Next to these structured characterizations, concepts sometimes have to be further defined using human-readable (&quot;informal&quot;) documentation. The non-mandatory <code><a href="http://www.w3.org/TR/skos-reference#definition" class="external-link" rel="nofollow">skos:definition</a></code>supplies a complete explanation of the intended meaning of a concept. The visual mappers displays the definition of the current concept, as the right hand button &quot;<strong>Definition</strong>&quot; is clicked.</p>
        <p><strong>Help</strong></p>
        <p>The right hand button &quot;<strong>Help</strong>&quot; activates this help section.</p>',
          'format' => 'full_html',
        ),
      ),
    );
  }

  /**
   * Get the default configuration for the Visual Mapper settings.
   *
   * @return array
   *   The configuration array.
   */
  private function getDefaultVisualMapperSettings() {
    return Yaml::decode(file_get_contents(drupal_get_path('module', 'smart_glossary') . '/smart_glossary.visual_mapper_defaults.yml'));
  }

  /**
   * Get the default configuration for the advanced settings.
   *
   * @return array
   *   The configuration array.
   */
  private function getDefaultAdvancedSettings() {
    return array(
      'interval' => '86400',
      'next_run' => 0,
      'char_a_z' => array(),
      'add_rdf_link' => FALSE,
      'add_endpoint_link' => FALSE,
      'semantic_connection' => array(
        'add_show_content_link' => FALSE,
        'show_in_destinations' => TRUE,
      ),
      'graph_uri' => '',
    );
  }

  /**
   * {@inheritdoc|}
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConnectionId($connection_id) {
    $this->connection_id = $connection_id;
    $this->connection = SemanticConnector::getConnection('sparql_endpoint', $this->connection_id);
  }

  /**
   * {@inheritdoc|}
   */
  public function setBasePath($base_path) {
    $this->base_path = $base_path;
  }

  /**
   * {@inheritdoc|}
   */
  public function setLanguageMapping($language_mapping) {
    $this->language_mapping = $language_mapping;
  }

  /**
   * {@inheritdoc|}
   */
  public function setVisualMapperSettings($settings) {
    $this->visual_mapper_settings = $settings + $this->getDefaultVisualMapperSettings();
  }

  /**
   * {@inheritdoc|}
   */
  public function setAdvancedSettings($settings) {
    $this->advanced_settings = $settings + $this->getDefaultAdvancedSettings();
  }
}