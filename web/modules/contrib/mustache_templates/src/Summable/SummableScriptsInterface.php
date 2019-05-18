<?php

namespace Drupal\mustache\Summable;

use Drupal\Core\Theme\ActiveTheme;

/**
 * Interface for providing summable scripts.
 */
interface SummableScriptsInterface {

  /**
   * Whether the usage of summable script files is enabled or not.
   *
   * @return bool
   *   Returns TRUE if summable script files are enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Get the library name for attaching the summable script of the given template.
   *
   * The returned library value might vary, e.g. depending on the current theme.
   * This method makes sure that the summable script file is available.
   *
   * @param string $template
   *   The name of the template.
   * @param \Drupal\Core\Theme\ActiveTheme|null $theme
   *   (Optional) When given, the lookup is based on this theme.
   *   By default, the lookup uses the currently active theme.
   *
   * @return string
   *   The library for the template.
   *
   * @throws \Drupal\mustache\Exception\MustacheTemplateNotFoundException
   *   When the template has not been found.
   * @throws \Drupal\mustache\Exception\MustacheFileException
   *   When the summable script file is not available.
   */
  public function getLibraryName($template, ActiveTheme $theme = NULL);

  /**
   * Get all libraries for every template as summable script file.
   *
   * @return array
   *   The libraries.
   */
  public function getAllLibraries();

  /**
   * Builds up the uri of the summable script file for the given template.
   *
   * @param string $provider
   *   Either 'module' or the theme name which provides the template.
   * @param string $template_name
   *   The template name.
   *
   * @return string
   *   The script file uri.
   */
  public function buildUri($provider, $template_name);

  /**
   * Generates the summable script file for the given name of the script library.
   *
   * @param string $library_name
   *   The script library name, as retrieved via ::getLibraryName().
   * @param bool $regenerate
   *   Whether to overwrite already existing script files (TRUE) or not (FALSE).
   *   By default, the file would only be generated if it does not exist yet.
   *
   * @return bool
   *   Returns TRUE when the script file was generated and is available, FALSE otherwise.
   *
   * @throws \Drupal\mustache\Exception\MustacheTemplateNotFoundException
   *   When the corresponding template has not been found.
   */
  public function generate($library_name, $regenerate = FALSE);

  /**
   * Deletes all generated summable script files.
   *
   * @return bool
   *   Returns TRUE on success, FALSE otherwise.
   */
  public function deleteAll();

}
