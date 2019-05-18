<?php

namespace Drupal\production_checklist;

/**
 * Interface ProductionChecklistInterface.
 */
interface ProductionChecklistInterface {

  /**
   * The checklist id.
   */
  const CHECKLIST_ID = 'production_checklist';

  /**
   * Returns a list of a the sections.
   *
   * Used for the checklist definition.
   *
   * @return array
   *   List of available sections.
   */
  public function getAvailableSections();

  /**
   * Returns a list of a the items grouped by sections.
   *
   * Used for the checklist definition.
   *
   * @return array
   *   List of available items by section.
   */
  public function getAvailableSectionsItems();

  /**
   * Returns a list of section titles from a list of section keys.
   *
   * @return array
   *   List of section titles.
   */
  public function getSectionTitles(array $sections);

  /**
   * Clears the items from the sections.
   *
   * @param array $sections
   *   List of sections that contains items.
   *
   * @return array
   *   List of items that have been cleared.
   */
  public function clearItems(array $sections);

  /**
   * Wrapper for the module handler to check if a module is installed.
   *
   * @param string $module
   *   Module machine name.
   *
   * @return bool
   *   Is the module installed.
   */
  public function isModuleInstalled($module);

  /**
   * Wrapper for the language manager to check if the site is multilingual.
   *
   * @return bool
   *   Is the site multilingual.
   */
  public function isSiteMultilingual();

  /**
   * Returns the link to a project on Drupal.org.
   *
   * @param string $project
   *   The project machine name.
   *
   * @return string
   *   Link to the project.
   */
  public function getProjectLink($project);

  /**
   * Returns the status of a project and its Drupal.org page.
   *
   * @param string $project
   *   The project machine name.
   * @param bool $should_install
   *   Indicates if the installation or uninstallation is recommended.
   *
   * @return string
   *   Markup for project status link.
   */
  public function getProjectStatusLink($project, $should_install = TRUE);

  /**
   * Returns a summary of projects status and links.
   *
   * @param array $projects
   *   Projects machine name list.
   * @param bool $should_install
   *   Indicates if the installation or uninstallation is recommended.
   *
   * @return string
   *   Projects status link list.
   */
  public function getProjectsListStatusLink(array $projects, $should_install = TRUE);

  /**
   * Returns a summary of the anti spam modules status and links.
   *
   * @return string
   *   Anti spam status link list.
   */
  public function getAntiSpamStatusLink();

  /**
   * Returns a summary of development modules status and links.
   *
   * @return string
   *   Development modules status link list.
   */
  public function getDevelopmentModulesStatusLink();

  /**
   * Returns the available updates for an update type.
   *
   * @param string $type
   *   Update type.
   *
   * @return string
   *   Available updates summary.
   */
  public function getAvailableUpdates($type = 'security');

  /**
   * Returns the amount of security updates that needs to be applied.
   *
   * @return int
   *   Amount of security updates.
   */
  public function getAvailableSecurityUpdatesAmount();

  /**
   * Returns the description and links depending on available security updates.
   *
   * @return array
   *   The security update checklist array.
   */
  public function getSecurityUpdatesChecklistArray();

  /**
   * Returns the module page link.
   *
   * @return string
   *   Markup for the modules page link.
   */
  public function getModulesPageLink();

  /**
   * Returns the module page link as a checklist array.
   *
   * @return array
   *   Text and url array keys.
   */
  public function getModulesPageTextUrl();

  /**
   * Returns the module uninstall page link as a checklist array.
   *
   * @return array
   *   Text and url array keys.
   */
  public function getModulesUninstallPageTextUrl();

  /**
   * Returns a list of fields for a type.
   *
   * @param string $type
   *   The field type.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   List of field configuration entities.
   */
  public function getFieldsFromType($type);

  /**
   * Returns the description for email obfuscation of fields and modules.
   *
   * @return string
   *   The email obfuscation description.
   */
  public function getEmailObfuscationDescription();

}
