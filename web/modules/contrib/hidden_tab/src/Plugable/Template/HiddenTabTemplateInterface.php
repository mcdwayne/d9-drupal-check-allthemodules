<?php

namespace Drupal\hidden_tab\Plugable\Template;

use Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase;

/**
 * Plugin providing twig templates.
 */
interface HiddenTabTemplateInterface extends HiddenTabPluginInterfaceBase {

  const PID = 'hidden_tab_template';

  /**
   * Id to label array of regions available in the template.
   *
   * @return array
   *   Id to label array of regions available in the template.
   */
  public function regions(): array;

  /**
   * Preview image of the template relative to drupal installation root if any.
   *
   * @return string|null
   *   Preview image of the template relative to drupal installation root, if
   *   any.
   */
  public function imageUri(): ?string;

  /**
   * Template file name, without twig.html extension.
   *
   * @return string|null
   *   Template filename , without twig.html extension.
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface::templateFilePath()
   */
  public function templateFile(): ?string;

  /**
   * Path of template file, relative to drupal installation root.
   *
   * Usually found out by drupal_get_path(...) . '/some_directory'
   *
   * @return string|null
   *   Path of Template file, relative to drupal installation root.
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface::templateFile()
   */
  public function templateFilePath(): ?string;

  /**
   * When using the template, gives a chance to fill #attach property.
   *
   * @return array|null
   *   When using the template, gives a chance to fill #attach property.
   */
  public function attachLibrary(): array;

  /**
   * For hook_theme(), see hidden_tab_theme().
   *
   * @return array
   *   Theme variables.
   *
   * @see \hidden_tab_theme()
   */
  public function themeVariables(): array;

}
