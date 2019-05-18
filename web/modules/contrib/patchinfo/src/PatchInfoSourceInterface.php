<?php

namespace Drupal\patchinfo;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for PatchInfo Source plugins.
 */
interface PatchInfoSourceInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Gets the plugin label.
   *
   * @return string
   *   Plugin label.
   */
  public function getLabel();

  /**
   * Gets patch information for a module from a patch source.
   *
   * @param array $info
   *   The parsed .info.yml file contents of the module to get patches for.
   * @param \Drupal\Core\Extension\Extension $file
   *   Full information about the module or theme to get patches for.
   * @param string $type
   *   Either 'module' or 'theme'.
   *
   * @return array
   *   An array of patch information arrays keyed by machine-readable name of
   *   target module. The patch information array for each target module is an
   *   integer-keyed array of patch information. The patch information is an
   *   array with two keys, 'info' and 'source'. The 'info' key contains the
   *   patch information, i.e. a string with a URL followed by any patch
   *   description. The URL is optional. 'source' is a string, that contains a
   *   human-readable source information for the patch information.
   *
   * @code
   * $return['ctools'] = [
   *   0 => [
   *     'info' => 'https://www.drupal.org/node/1739718 Issue 1739718, Patch #32',
   *     'source' => 'modules/contrib/ctools/ctools.info.yml',
   *   ],
   * ];
   * @endcode
   */
  public function getPatches(array $info, Extension $file, $type);

}
