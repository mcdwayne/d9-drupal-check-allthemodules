<?php

namespace Drupal\Tests\onlyone\Traits;

/**
 * Trait for common function to Unit tests.
 */
trait OnlyOneUnitTestTrait {

  /**
   * Returns an array of content types objects.
   *
   * Each index determine a set of content types.
   *
   * @return array
   *   An array of content types objects.
   */
  public function getContentTypesObjectList() {

    $content_types = [
      // Test 1.
      [
        'page' => [
          (object) [
            'language' => 'en',
            'total' => 1,
            'configured' => TRUE,
            'name' => 'Basic Page',
          ],
          (object) [
            'language' => 'es',
            'total' => 1,
            'configured' => TRUE,
            'name' => 'Basic Page',
          ],
        ],
        'blog' => [
          (object) [
            'language' => '',
            'total' => 0,
            'configured' => TRUE,
            'name' => 'Blog Post',
          ],
        ],
        'car' => [
          (object) [
            'language' => 'und',
            'total' => 1,
            'configured' => FALSE,
            'name' => 'Car',
          ],
          (object) [
            'language' => 'xzz',
            'total' => 2,
            'configured' => FALSE,
            'name' => 'Car',
          ],
          (object) [
            'language' => 'en',
            'total' => 1,
            'configured' => FALSE,
            'name' => 'Car',
          ],
        ],
        'article' => [
          (object) [
            'language' => 'und',
            'total' => 1,
            'configured' => FALSE,
            'name' => 'Article',
          ],
          (object) [
            'language' => 'en',
            'total' => 2,
            'configured' => FALSE,
            'name' => 'Article',
          ],
          (object) [
            'language' => 'es',
            'total' => 1,
            'configured' => FALSE,
            'name' => 'Article',
          ],
        ],
      ],
      // Test 2.
      [
        'blog' => [
          (object) [
            'language' => 'en',
            'total' => 1,
            'configured' => TRUE,
            'name' => 'Blog Post',
          ],
        ],
        'car' => [
          (object) [
            'language' => '',
            'total' => 0,
            'configured' => FALSE,
            'name' => 'Car',
          ],
        ],
      ],
      // Test 3.
      [
        'page' => [
          (object) [
            'language' => 'en',
            'total' => 1,
            'configured' => TRUE,
            'name' => 'Basic Page',
          ],
          (object) [
            'language' => 'es',
            'total' => 1,
            'configured' => TRUE,
            'name' => 'Basic Page',
          ],
        ],
        'car' => [
          (object) [
            'language' => '',
            'total' => 0,
            'configured' => TRUE,
            'name' => 'Car',
          ],
        ],
        'article' => [
          (object) [
            'language' => 'es',
            'total' => 3,
            'configured' => FALSE,
            'name' => 'Article',
          ],
        ],
      ],
      // Test 4.
      [
        'page' => [
          (object) ['total' => 1, 'configured' => TRUE, 'name' => 'Basic Page'],
        ],
        'blog' => [
          (object) ['total' => 2, 'configured' => TRUE, 'name' => 'Blog Post'],
        ],
        'car' => [
          (object) ['total' => 0, 'configured' => FALSE, 'name' => 'Car'],
        ],
        'article' => [
          (object) ['total' => 5, 'configured' => FALSE, 'name' => 'Article'],
        ],
      ],
      // Test 5.
      [
        'blog' => [
          (object) ['total' => 0, 'configured' => TRUE, 'name' => 'Blog Post'],
        ],
        'car' => [
          (object) ['total' => 1, 'configured' => FALSE, 'name' => 'Car'],
        ],
      ],
      // Test 6.
      [
        'page' => [
          (object) ['total' => 1, 'configured' => TRUE, 'name' => 'Basic Page'],
        ],
        'car' => [
          (object) ['total' => 5, 'configured' => TRUE, 'name' => 'Car'],
        ],
        'article' => [
          (object) ['total' => 3, 'configured' => FALSE, 'name' => 'Article'],
        ],
      ],
    ];

    // Getting the language labels.
    $language_labels = array_column($this->getLanguageMap(), 1, 0);

    // Adding the total nodes information to the array of expected values.
    for ($i = 0; $i < 6; $i++) {
      foreach ($content_types[$i] as $conten_type => $languages) {
        foreach ($languages as $language => $values) {
          // Adding the total nodes information for multilingual sites.
          if ($i < 3) {
            $total_nodes = $values->total ? $this->getStringTranslationStub()->formatPlural($values->total, '@language: @total Node', '@language: @total Nodes', ['@language' => $language_labels[$values->language], '@total' => $values->total]) : $this->getStringTranslationStub()->translate('0 Nodes');
          }
          else {
            // Adding the total nodes information for non-multilingual sites.
            $total_nodes = $values->total ? $this->getStringTranslationStub()->formatPlural($values->total, '@total Node', '@total Nodes', ['@total' => $values->total]) : $this->getStringTranslationStub()->translate('0 Nodes');
          }
          // Adding the total nodes information.
          $content_types[$i][$conten_type][$language]->total_nodes = $total_nodes;
        }
      }
    }

    return $content_types;
  }

  /**
   * Returns an array of language labels.
   *
   * @return array
   *   An array of languages labels keyed by language code.
   */
  public function getLanguageMap() {
    // The language map.
    $language_map = [
      ['en', 'En'],
      ['es', 'Es'],
      ['', 'Not specified'],
      ['und', 'Not specified'],
      ['xzz', 'Not applicable'],
    ];

    return $language_map;
  }

  /**
   * Returns a content type label list.
   *
   * @return array
   *   An associative array with the content type machine name as key
   *   and his label as value.
   */
  public function getContentTypesList() {
    // The content types list.
    $content_types_list = [
      'page' => 'Basic Page',
      'blog' => 'Blog Post',
      'car' => 'Car',
      'article' => 'Article',
    ];

    return $content_types_list;
  }

}
