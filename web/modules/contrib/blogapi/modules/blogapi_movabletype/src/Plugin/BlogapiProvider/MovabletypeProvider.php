<?php

namespace Drupal\blogapi_movabletype\Plugin\BlogapiProvider;

use Drupal\blogapi\ProviderBase;

/**
 * Class MovabletypeProvider.
 *
 * @package Drupal\blogapi_movabletype\Plugin\BlogapiProvider
 *
 * @Provider(
 *   id = "MovabletypeProvider",
 *   name = @Translation("Movable Type BlogAPI Provider")
 * )
 */
class MovabletypeProvider extends ProviderBase {

  /**
   * Returns implemented methods.
   *
   * @return array
   *   Array of implemented methods.
   */
  public static function getMethods() {
    $methods = [
      [
        'mt.publishPost',
        'blogapi_movabletype_publish_post',
        [
          'string',
          'int',
          'string',
          'string',
        ],
        'Publish (rebuild) all of the static files related to an entry from your blog. Equivalent to saving an entry in the system (but without the ping).',
      ],
      [
        'mt.getRecentPostTitles',
        'blogapi_movabletype_get_recent_posts',
        [
          'string',
          'string',
          'string',
          'string',
          'int',
        ],
        'Returns a bandwidth-friendly list of the most recent posts in the system.',
      ],
      [
        'mt.supportedTextFilters',
        'blogapi_movabletype_text_filters',
        [
          'string',
        ],
        'Retrieve information about the text formatting plugins supported by the server.',
      ],
      [
        'mt.getPostCategories',
        'blogapi_movabletype_post_categories',
        [
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns a list of all categories to which the post is assigned.',
      ],
      [
        'mt.getCategoryList',
        'blogapi_movabletype_category_list',
        [
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns a list of all categories defined in the blog.',
      ],
      [
        'mt.setPostCategories',
        'blogapi_movabletype_set_categories',
        [
          'string',
          'string',
          'string',
          'string',
          'struct',
        ],
        'Sets the categories for a post.',
      ],
      [
        'mt.supportedMethods',
        'blogapi_movabletype_supported_methods',
        [
          'string',
        ],
        'Retrieve information about the XML-RPC methods supported by the server.',
      ],
    ];
    return $methods;
  }

}
