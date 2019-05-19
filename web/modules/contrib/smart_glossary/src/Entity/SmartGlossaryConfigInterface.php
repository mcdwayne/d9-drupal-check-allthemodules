<?php

/**
 * @file Contains \Drupal\smart_glossary\Entity\SmartGlossaryConfigInterface.
 */

namespace Drupal\smart_glossary\Entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface SmartGlossaryConfigInterface extends ConfigEntityInterface
{
  /**
   * The Getter method for the title variable.
   *
   * @return string
   *   The title of the Smart Glossary configuration.
   */
  public function getTitle();

  /**
   * Set the title of the Smart Glossary configuration.
   *
   * @param string $title
   *   The title of the Smart Glossary configuration.
   */
  public function setTitle($title);

  /**
   * The Getter method for the connection variable.
   *
   * @return \Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection
   *   The connection to the SPARQL endpoint.
   */
  public function getConnection();

  /**
   * The Getter method for the connection_id variable.
   *
   * @return string
   *   The ID of the Sparql Endpoint connection.
   */
  public function getConnectionID();

  /**
   * Set the connection ID of the PoolParty GraphSearch server.
   *
   * @param string $connection_id
   *   The ID of the SemanticConnectorPPServerConnection.
   */
  public function setConnectionId($connection_id);

  /**
   * The Getter method for the base_path variable.
   *
   * @return string
   *   The basepath of the Smart Glossary configuration.
   */
  public function getBasePath();

  /**
   * The Setter method for the base_path variable.
   *
   * @param string $base_path
   *   The basepath of the Smart Glossary configuration.
   */
  public function setBasePath($base_path);

  /**
   * The Getter method for the language_mapping variable.
   *
   * @return array
   *   Associative array of language mappings by language.
   */
  public function getLanguageMapping();

  /**
   * The Setter method for the language_mapping variable.
   *
   * @param array $language_mapping
   *   Array of language mappings.
   */
  public function setLanguageMapping($language_mapping);

  /**
   * The Getter method for the visual_mapper_settings variable.
   *
   * @return array
   *   Array of settings regarding the Visual Mapper.
   */
  public function getVisualMapperSettings();

  /**
   * Setter-function for the visual_mapper_settings variable.
   *
   * @param array $settings
   *   Array of Visual Mapper settings.
   */
  public function setVisualMapperSettings($settings);

  /**
   * The Getter method for the advanced_settings variable.
   *
   * @return array
   *   Array of advanced settings.
   */
  public function getAdvancedSettings();

  /**
   * Setter-function for the advanced_settings variable.
   *
   * @param array $settings
   *   Array of advanced settings.
   */
  public function setAdvancedSettings($settings);
}