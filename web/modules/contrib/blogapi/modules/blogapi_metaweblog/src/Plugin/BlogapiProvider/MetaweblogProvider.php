<?php

namespace Drupal\blogapi_metaweblog\Plugin\BlogapiProvider;

use Drupal\blogapi\ProviderBase;

/**
 * Class MetaweblogProvider.
 *
 * @package Drupal\blogapi_metaweblog\Plugin\BlogapiProvider
 *
 * @Provider(
 *   id = "MetaweblogProvider",
 *   name = @Translation("metaWeblog BlogAPI Provider")
 * )
 */
class MetaweblogProvider extends ProviderBase {

  /**
   * Returns implemented methods.
   *
   * @return array
   *   Array of implemented methods.
   */
  public static function getMethods() {
    $methods = [
      [
        'metaWeblog.getRecentPosts',
        'blogapi_metaweblog_get_recent_posts',
        [
          'string',
          'string',
          'string',
          'string',
          'int',
        ],
        'Returns a list of the most recent posts in the system.',
      ],
      [
        'metaWeblog.editPost',
        'blogapi_metaweblog_edit_post',
        [
          'string',
          'string',
          'string',
          'string',
          'struct',
          'boolean',
        ],
        'Updates information about an existing post.',
      ],
      [
        'metaWeblog.newPost',
        'blogapi_metaweblog_new_post',
        [
          'string',
          'string',
          'string',
          'string',
          'struct',
          'boolean',
        ],
        'Updates information about an existing post.',
      ],
      [
        'metaWeblog.newMediaObject',
        'blogapi_metaweblog_new_media',
        [
          'string',
          'string',
          'string',
          'string',
          'struct',
        ],
        'Uploads a file to your server.',
      ],
      [
        'metaWeblog.getPost',
        'blogapi_metaweblog_get_post',
        [
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns information about a specific post.',
      ],
      [
        'metaWeblog.getCategories',
        'blogapi_metaweblog_get_categories',
        [
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns a list of all categories to which the post is assigned.',
      ],
    ];
    return $methods;
  }

}
