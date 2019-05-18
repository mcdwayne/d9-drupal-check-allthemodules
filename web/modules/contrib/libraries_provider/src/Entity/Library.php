<?php

namespace Drupal\libraries_provider\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Library configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "library",
 *   label = @Translation("Library"),
 *   label_collection = @Translation("Libraries"),
 *   label_singular = @Translation("library"),
 *   label_plural = @Translation("libraries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count library",
 *     plural = "@count libraries",
 *   ),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_prefix = "library",
 *   config_export = {
 *     "id",
 *     "label",
 *     "enabled",
 *     "version",
 *     "source",
 *     "minified",
 *     "variant",
 *     "replaces",
 *     "custom_options",
 *   }
 * )
 */
class Library extends ConfigEntityBase {

  /**
   * The library machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the library entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The status of this library.
   *
   * This a different concept from the status of the config entity.
   *
   * @var bool
   */
  protected $enabled;

  /**
   * The library version.
   *
   * @var string
   */
  protected $version;

  /**
   * The source of this library.
   *
   * @var string
   */
  protected $source;

  /**
   * When to Serve the library minified.
   *
   * @var string
   */
  protected $minified;

  /**
   * The library variant used.
   *
   * @var string
   */
  protected $variant;

  /**
   * The libraries that this library replaces.
   *
   * @var array
   */
  protected $replaces;

  /**
   * A set of personalizations for the library.
   *
   * @var array
   */
  protected $custom_options;

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add a dependency for the extension that defines the library.
    list($extension,) = explode('__', $this->id);
    $type = \Drupal::service('module_handler')->moduleExists($extension) ? 'module' : 'theme';
    $this->addDependency($type, $extension);

    // Add a dependency for the the module that provides the source plugin.
    $definition = \Drupal::service('plugin.manager.library_source')->getDefinition($this->source);
    $this->addDependency('module', $definition['provider']);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $this->applyCustomOptions();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    foreach ($entities as $library) {
      $library->applyCustomOptions(TRUE);
    }
  }

  /**
   * Perform changes to customize the library.
   */
  protected function applyCustomOptions($forceDefault = FALSE) {
    $customOptions = $forceDefault ? [] : $this->get('custom_options');
    if ($customOptions || $forceDefault) {
      list($extension, $libraryName) = explode('__', $this->id());
      $libraryDefinition = \Drupal::service('library.discovery')->getLibraryByName($extension, $libraryName);
      $requirements = \Drupal::service('libraries_provider.manager')->getCustomOptionsRequirements($libraryDefinition['libraries_provider']['custom_options'] ?? []);
      if (!$requirements) {
        foreach ($libraryDefinition['libraries_provider']['custom_options']['process'] ?? [] as $processType => $processConfiguration) {
          if ($processType === 'sass') {
            $sourceImports = '';
            foreach ($processConfiguration['source'] as $source) {
              if ($source === 'custom_options') {
                foreach ($customOptions as $variableName => $variableValue) {
                  $sourceImports .= $variableName . ": " . $variableValue . ";\n";
                }
              }
              else {
                $source = \Drupal::service('token')->replace($source, ['library' => $this]);
                $sourceImports .= "@import \"" . $source . "\";\n";
              }
            }
            $sass = new \Sass();
            $sass->setStyle(\Sass::STYLE_COMPRESSED);
            $css = $sass->compile($sourceImports);
            $cssPath = reset($libraryDefinition['css'])['data'];
            file_put_contents($cssPath, $css);
          }
        }
      }
    }
  }

}
