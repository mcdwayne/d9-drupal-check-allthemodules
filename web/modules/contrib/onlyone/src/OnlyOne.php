<?php

namespace Drupal\onlyone;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class OnlyOne.
 */
class OnlyOne implements OnlyOneInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The onlyone service.
   *
   * @var \Drupal\onlyone\OnlyOnePrintStrategyInterface
   */
  protected $formatter;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
    $this->formatter = new OnlyOnePrintAdminPage();
  }

  /**
   * Returns the temporary table name created with all the content types names.
   *
   * @return string
   *   The temporary table name.
   */
  private function getTemporaryContentTypesTableName() {
    // Looking for all the content types, as the content types are only
    // accesibles through the entity_type.manager service we can't use a
    // LEFT JOIN in only one query as is posible in Drupal 7
    // (See Drupal 7 module version).
    // @see https://drupal.stackexchange.com/q/233214/28275
    // @see https://drupal.stackexchange.com/q/235640/28275
    // Looking for the content type names from the config table.
    $query_content_types = "SELECT DISTINCT SUBSTR(name, 11) AS type
                            FROM {config}
                            WHERE name LIKE 'node.type.%'";
    // Creating a temporary table and returning the name.
    return $this->connection->queryTemporary($query_content_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypesList() {
    // Looking for all the content types, as the content types are only
    // accesibles through the entity_type.manager service we can't use a
    // LEFT JOIN in only one query as is posible in Drupal 7
    // (See Drupal 7 module version).
    // @see http://drupal.stackexchange.com/q/233214/28275
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    // Creating an array with all content types.
    $content_types_list_label = [];
    foreach ($content_types as $content_type) {
      $content_types_list_label[$content_type->id()] = $content_type->label();
    }

    return $content_types_list_label;
  }

  /**
   * {@inheritdoc}
   */
  public function existsNodesContentType($content_type, $language = NULL) {
    // Getting the entity query instance.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type', $content_type);
    // The site is multilingual?
    if ($this->languageManager->isMultilingual()) {
      if (empty($language)) {
        $language = $this->languageManager->getCurrentLanguage()->getId();
      }
      // Adding the language condition.
      $query->condition('langcode', $language);
    }
    $nids = $query->execute();
    // Extracting the nid from the array.
    $nid = count($nids) ? array_pop($nids) : 0;

    return $nid;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteContentTypeConfig($content_type) {
    // Getting the config file.
    $config = $this->configFactory->getEditable('onlyone.settings');
    // Getting the content types configured to have onlyone node.
    $onlyone_content_types = $config->get('onlyone_node_types');
    // Checking if the config exists.
    $index = array_search($content_type, $onlyone_content_types);
    if ($index !== FALSE) {
      // Deleting the value from the array.
      unset($onlyone_content_types[$index]);
      // Saving the values in the config.
      $config->set('onlyone_node_types', $onlyone_content_types)->save();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageLabel($language) {
    switch ($language) {
      case LanguageInterface::LANGCODE_NOT_SPECIFIED:
      case '':
        // If the language is empty then is Not specified.
        return $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_SPECIFIED)->getName();

      case LanguageInterface::LANGCODE_NOT_APPLICABLE:
        return $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE)->getName();

      default:
        return ucfirst($language);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContentTypes() {
    // Getting the temporary table with all the content type names.
    $content_types_temporary_table_name = $this->getTemporaryContentTypesTableName();
    // Multilingual site?
    if ($this->languageManager->isMultilingual()) {
      // Looking for content types that doesn't have more than 1 node
      // in any language.
      $query = "SELECT type
                FROM $content_types_temporary_table_name
                WHERE type NOT IN
                    (SELECT node.type
                     FROM {node} node
                     JOIN {node_field_data} node_field_data USING(nid)
                     GROUP BY type,
                              node_field_data.langcode
                     HAVING COUNT(nid) > 1)
                ORDER BY type ASC";
    }
    else {
      // If the site is not multilingual we have only one language.
      $query = "SELECT node_type.type
                FROM $content_types_temporary_table_name node_type
                LEFT JOIN {node} USING(type)
                GROUP BY type
                HAVING COUNT(nid) <= 1
                ORDER BY type ASC";
    }
    // Executing the query.
    $result = $this->connection->query($query);
    // Getting the content types machine names.
    $content_types = $result->fetchCol();

    return $content_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotAvailableContentTypes() {
    // Multilingual site?
    if ($this->languageManager->isMultilingual()) {
      // Looking for content types with more than 1 node
      // in at least one language.
      $query = 'SELECT DISTINCT node.type
                FROM {node} node
                JOIN {node_field_data} node_field_data USING(nid)
                GROUP BY type,
                         node_field_data.langcode
                HAVING COUNT(nid) > 1
                ORDER BY node.type ASC';
    }
    else {
      // If the site is not multilingual we have only one language,
      // and if we have a content type with more than one node,
      // then it is not available for the Only One feature.
      // Searching all the content types with nodes.
      $query = "SELECT type
                FROM {node}
                GROUP BY type
                HAVING COUNT(nid) > 1
                ORDER BY type ASC";
    }
    // Executing the query.
    $result = $this->connection->query($query);
    // Getting the content types machine names.
    $content_types = $result->fetchCol();

    return $content_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContentTypesSummarized() {
    // Getting the temporary table with all the content type names.
    $content_types_temporary_table_name = $this->getTemporaryContentTypesTableName();
    // Multilingual site?
    if ($this->languageManager->isMultilingual()) {
      // Looking for content types that doesn't have more than 1 node
      // in any language.
      $query = "SELECT DISTINCT node_type.type,
                                node_field_data.langcode AS language,
                                COUNT(node.nid) AS total
                FROM $content_types_temporary_table_name node_type
                LEFT JOIN {node} node USING(type)
                LEFT JOIN {node_field_data} node_field_data USING(nid)
                WHERE node_type.type NOT IN
                    (SELECT node.type
                     FROM {node} node
                     JOIN {node_field_data} node_field_data USING(nid)
                     GROUP BY type,
                              node_field_data.langcode
                     HAVING COUNT(nid) > 1)
                GROUP BY type,
                         node_field_data.langcode
                ORDER BY node_type.type ASC";
    }
    else {
      // If the site is not multilingual we have only one language.
      $query = "SELECT type,
                       COUNT(nid) AS total
                FROM $content_types_temporary_table_name node_type
                LEFT JOIN {node} USING(type)
                GROUP BY type
                HAVING COUNT(nid) <= 1
                ORDER BY type ASC";
    }
    // Executing the query.
    $result = $this->connection->query($query);
    // Getting the information keyed by content type machine name.
    $content_types = $result->fetchAll(\PDO::FETCH_GROUP);

    // Adding content type name and other information.
    $this->addAditionalInfoToContentTypes($content_types);

    return $content_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotAvailableContentTypesSummarized() {
    // Multilingual site?
    if ($this->languageManager->isMultilingual()) {
      // Looking for content types that doesn't have more than 1 node
      // in any language.
      $query = "SELECT DISTINCT node.type,
                                node_field_data.langcode AS language,
                                COUNT(node.nid) AS total
                FROM {node} node
                JOIN {node_field_data} node_field_data USING(nid)
                WHERE node.type IN
                    (SELECT DISTINCT node.type
                     FROM {node} node
                     JOIN {node_field_data} node_field_data USING(nid)
                     GROUP BY type,
                              node_field_data.langcode
                     HAVING COUNT(nid) > 1)
                GROUP BY node.type,
                         language
                ORDER BY node.type ASC";
    }
    else {
      // If the site is not multilingual we have only one language.
      $query = "SELECT type,
                       COUNT(nid) AS total
                FROM {node}
                GROUP BY type
                HAVING COUNT(nid) > 1
                ORDER BY type ASC";
    }
    // Executing the query.
    $result = $this->connection->query($query);
    // Getting the information keyed by content type machine name.
    $content_types = $result->fetchAll(\PDO::FETCH_GROUP);

    // Adding content type name and other information.
    $this->addAditionalInfoToContentTypes($content_types);

    return $content_types;
  }

  /**
   * Add additional information to the content types.
   *
   * @param array $content_types
   *   The content types.
   */
  private function addAditionalInfoToContentTypes(array &$content_types) {
    // Getting the content types label list.
    $content_types_list_label = $this->getContentTypesList();
    // Getting configured content types.
    $configured_content_types = $this->configFactory->get('onlyone.settings')->get('onlyone_node_types');
    // Iterating over all the content types.
    foreach ($content_types as $conten_type => $content_type_info) {
      // Getting the total of languages.
      $cant = count($content_type_info);
      // Iterating over each language.
      for ($i = 0; $i < $cant; $i++) {
        $content_types[$conten_type][$i]->configured = in_array($conten_type, $configured_content_types) ? TRUE : FALSE;
        // Adding the content type name.
        $content_types[$conten_type][$i]->name = $content_types_list_label[$conten_type];
        // The format for multilingual is diferent from non multilingual sites.
        if ($this->languageManager->isMultilingual()) {
          // Getting the language label.
          $language = $this->getLanguageLabel($content_type_info[$i]->language);
          // Adding text with total nodes.
          $total_nodes = $content_type_info[$i]->total ? $this->formatPlural($content_type_info[$i]->total, '@language: @total Node', '@language: @total Nodes', ['@language' => $language, '@total' => $content_type_info[$i]->total]) : $this->t('0 Nodes');
        }
        else {
          // Adding text with total nodes.
          $total_nodes = $content_type_info[$i]->total ? $this->formatPlural($content_type_info[$i]->total, '@total Node', '@total Nodes', ['@total' => $content_type_info[$i]->total]) : $this->t('0 Nodes');
        }
        // Adding the total nodes information.
        $content_types[$conten_type][$i]->total_nodes = $total_nodes;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFormatter(OnlyOnePrintStrategyInterface $formatter) {
    $this->formatter = $formatter;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContentTypesForPrint() {
    return $this->formatter->getContentTypesListForPrint($this->getAvailableContentTypesSummarized());
  }

  /**
   * {@inheritdoc}
   */
  public function getNotAvailableContentTypesForPrint() {
    return $this->formatter->getContentTypesListForPrint($this->getNotAvailableContentTypesSummarized());
  }

}
