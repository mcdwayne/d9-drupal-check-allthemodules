<?php

/**
 * @file
 * Contains \Drupal\isotope\Controller\IsotopeController.
 */

namespace Drupal\isotope\Controller;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for admin block routes.
 */
class IsotopeController extends ControllerBase {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transliteration'),
      $container->get('language_manager')
    );
  }

  public function __construct(TransliterationInterface $transliteration, LanguageManagerInterface $languageManager) {
    $this->transliteration = $transliteration;
    $this->languageManager = $languageManager;
  }

  /**
   * Given a layout name return the name of a library to load.
   *
   * @param string|array $properties
   *   Name of layout mode or other plugin.
   *
   * @return array
   *   Array of library names to load, keyed by property name.
   */
  public static function libraryMapping($properties) {
    $properties = (array) $properties;
    $mappings = [
      'cellsByColumn' => 'isotope/isotope-cells-by-column',
      'cellsByRow' => 'isotope/isotope-cells-by-row',
      'fitColumns' => 'isotope/isotope-fit-columns',
      'fitRows' => 'isotope/isotope-layout',
      'horiz' => 'isotope/isotope-horizontal',
      'imagesloaded' => 'isotope/imagesloaded',
      'masonry' => 'isotope/isotope-layout',
      'masonryHorizontal' => 'isotope/isotope-masonry-horizontal',
      'packery' => 'isotope/packery',
      'vertical' => 'isotope/isotope-layout',
    ];
    return array_intersect_key($mappings, array_flip($properties));
  }

  /**
   * Convert a snake_case string to a camelCase string.
   *
   * @param string $raw
   *   String in snake_case.
   *
   * @return string
   *   String in camelCase.
   */
  public static function snakeToCamel($raw) {
    return lcfirst(str_replace(' ', '', ucwords(str_replace([
      '-',
      '_',
    ], ' ', $raw))));
  }

  /**
   * Strip all special chars to make value suitable for css class name.
   *
   * @param string|array $raw
   *   The raw value to be sanitized. Arrays will be imploded by space.
   *
   * @return string
   *   The sanitized value.
   */
  public function sanitize($raw) {
    $safe = $raw;

    // Recursively sanitize arrays.
    if (is_array($raw)) {
      $safe = [];
      foreach ($raw as $i) {
        $safe[] = $this->sanitize($i);
      }
      return implode(' ', $safe);
    }

    // Transliterate non-latin characters.
    // @TODO: Using services means stop using static.
    $language_code = $this->languageManager->getDefaultLanguage()->getId();
    $safe = $this->transliteration->transliterate($safe, $language_code);

    // Basic class-name rules.
    $safe = strtolower($safe);
    $safe = preg_replace('/[^a-z0-9]/s', '-', $safe);
    $safe = preg_replace('/-{2,}/', '-', $safe);

    return $safe;
  }


  /**
   * Returns a demo page
   *
   * @TODO: This should be in an example sub-module.
   *
   * @return array
   *   A #type 'page' render array containing the block region demo.
   */
  public function demo() {

    $render[] = [
      '#markup' => $this->t('<p>You can explore this example for yourself at <strong>___</strong>.</p>'),
    ];

    // The label can be anything, including images, etc. Filtering happens on the
    // key.
    $colors = [
      'blue' => 'Blue',
      'red' => '<span class="thisIsCustomisedLabel">Red</span>',
      'yellow' => 'Yellow',
    ];
    $render[] = [
      '#theme' => 'isotope_filter',
      '#items' => $colors,
      // Optional filter_name: unique to distinguish it from the other filters on
      // the page.
      '#filter_name' => 'color',
      // Optional filter_title: displayed as a list title.
      '#filter_title' => $this->t('Colour'),
    ];

    $sizes = [
      'small' => 'Small',
      'wide' => 'Wide',
      'big' => 'Big',
      'tall' => 'Tall',
    ];
    $render[] = [
      '#theme' => 'isotope_filter',
      '#items' => $sizes,
      '#filter_name' => 'size',
      '#filter_title' => $this->t('Size'),
    ];

    $shapes = [
      'round' => 'Round',
      // Transliterate non-latin characters.
      'square (naïve jalapeño pâté)' => 'Square',
    ];
    $render[] = [
      '#theme' => 'isotope_filter',
      '#items' => $shapes,
      '#filter_name' => 'shape',
      '#filter_title' => $this->t('Shapes'),
      '#instance' => 1,
    ];

    $sorts = [
      'Size' => 'size',
      'shape',
      'color',
      ['color', 'size'],
      'Color then Shape' => ['color', 'shape'],
    ];
    $render[] = [
      '#theme' => 'isotope_sorter',
      '#sorts' => $sorts,
      '#default_label' => $this->t('Original'),
      '#instance' => 1,
    ];

    // Create items of every size shape and color.
    $items = [];
    foreach ($sizes as $size => $label1) {
      foreach ($shapes as $shape => $label2) {
        foreach ($colors as $color => $label3) {
          $items[] = [
            'value' => '<p>Item</p>',
            'data' => [
              'size' => $size,
              'shape' => $shape,
              'color' => $color,
            ],
          ];
        }
      }
    }

    $render['grid'] = [
      '#theme' => 'isotope_grid',
      '#items' => $items,
      '#instance' => 1,
      '#attached' => ['library' => ['isotope/isotope-example']],
    ];

    return $render;
  }

}
