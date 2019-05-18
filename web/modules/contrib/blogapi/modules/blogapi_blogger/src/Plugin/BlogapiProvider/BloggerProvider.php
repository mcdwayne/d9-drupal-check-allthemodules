<?php

namespace Drupal\blogapi_blogger\Plugin\BlogapiProvider;

use Drupal\blogapi\ProviderBase;

/**
 * Class BloggerProvider.
 *
 * @package Drupal\blogapi_blogger\Plugin\BlogapiProvider
 *
 * @Provider(
 *   id = "BloggerProvider",
 *   name = @Translation("Blogger BlogAPI Provider")
 * )
 */
class BloggerProvider extends ProviderBase {

  /**
   * Returns implemented methods.
   *
   * @return array
   *   Array of implemented methods.
   */
  public static function getMethods() {
    $methods = [
      [
        'blogger.deletePost',
        'blogapi_blogger_delete_post',
        [
          'string',
          'string',
          'string',
          'string',
          'string',
          'boolean',
        ],
        'Deletes a post.',
      ],
      [
        'blogger.getUsersBlogs',
        'blogapi_blogger_get_users_blogs',
        [
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns a list of blogs to which an author has posting privileges.',
      ],
      [
        'blogger.getUserInfo',
        'blogapi_blogger_get_user_info',
        [
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns information about an author in the system.',
      ],
      [
        'blogger.newPost',
        'blogapi_blogger_new_post',
        [
          'string',
          'string',
          'string',
          'string',
          'string',
          'string',
          'boolean',
        ],
        'Creates a new post, and optionally publishes it.',
      ],
      [
        'blogger.editPost',
        'blogapi_blogger_edit_post',
        [
          'string',
          'string',
          'string',
          'string',
          'string',
          'string',
          'boolean',
        ],
        'Updates the information about an existing post.',
      ],
      [
        'blogger.getPost',
        'blogapi_blogger_get_post',
        [
          'string',
          'string',
          'string',
          'string',
          'string',
        ],
        'Returns information about a specific post.',
      ],
      [
        'blogger.getRecentPosts',
        'blogapi_blogger_get_recent_posts',
        [
          'string',
          'string',
          'string',
          'string',
          'string',
          'int',
        ],
        'Returns a list of the most recent posts in the system.',
      ],
    ];
    return $methods;
  }

}
