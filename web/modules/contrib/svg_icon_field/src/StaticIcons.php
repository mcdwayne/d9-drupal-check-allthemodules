<?php

namespace Drupal\svg_icon_field;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Icons class.
 */
class StaticIcons {

  use StringTranslationTrait;
  protected $stringTranslation;

  /**
   * Constructs a new StaticIcons object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Get the category for given id or all.
   *
   * @param string $id
   *   ID of the category.
   *
   * @return array
   *   Array of categories or single category.
   */
  public function getCategoriesStructure($id = NULL) {
    $categories = Yaml::decode(file_get_contents(drupal_get_path('module', 'svg_icon_field') . '/icons/icons.categories.yaml'));
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('svg_icon_field_categories_alter', [&$categories]);
    return (!empty($id)) ? $categories['categories'][$id] : $categories;
  }

  /**
   * Get icons by directory or return all if dir is null.
   *
   * @param string $path
   *   Path to directory with icons.
   *
   * @return array
   *   Array of options to feed an icon field.
   */
  public function getIcons($path = NULL) {
    global $base_path;

    if (empty($path)) {
      $base_dir = scandir(drupal_get_path('module', 'svg_icon_field') . "/icons");
      foreach ($base_dir as $sub_dir) {
        if ((substr($sub_dir, 0, 1) === '.')) {
          $path = $sub_dir;
          break;
        }
      }
    }

    $files = scandir($path);
    foreach ($files as $file) {
      if ($file != '.' && $file != '..' && (substr($file, 0, 1) != '.')) {
        $options[$file] = $base_path . $path . '/' . $file;
      }
    }

    return $options;
  }

  /**
   * Get dir path to category.
   *
   * @param string $category
   *   ID of the category.
   *
   * @return string
   *   Path to directory where icons are storred.
   */
  public function getCategoryLocation($category) {
    $locations = $this->getCategoriesStructure();
    $type = $locations['categories'][$category]['element_type'];
    $name = $locations['categories'][$category]['element_name'];
    $path = $locations['categories'][$category]['icons_path'];
    $path_to_element = drupal_get_path($type, $name);
    return $path_to_element . '/' . $path;
  }

  /**
   * Get category attribution.
   *
   * Sometimes there are icons which requires crediting of an author.
   * That's an attribution. This function is going to get it.
   *
   * @param string $category
   *   ID of the category.
   *
   * @return string
   *   String or html of the attribution.
   */
  public function getCategoryAttribution($category) {
    // Get categories with its lables.
    $categories = $this->getCategoriesStructure();
    return $categories['categories'][$category]['attribution'];
  }

  /**
   * Get human readable icon categories based on yaml file.
   *
   * @return array
   *   Complete array of categories.
   */
  public function getHumanReadableIconCategories() {
    $category_options = [];

    // Get categories with its lables.
    $categories = $this->getCategoriesStructure();

    $categories = $categories['categories'];
    foreach ($categories as $category_key => $category_value) {
      $category_options[$this->t($category_value['group'])->render()][$category_key] = $category_value['label'];
    }

    return $category_options;
  }

}
