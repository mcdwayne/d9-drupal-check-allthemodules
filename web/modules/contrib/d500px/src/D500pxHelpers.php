<?php

namespace Drupal\D500px;

/**
 * Drupal 500px Helper class.
 *
 * @package Drupal\D500px
 */
class D500pxHelpers {

  /**
   * Helper method to prepare a photo just after we retrieved it from 500px.
   *
   * @param object $photo_obj
   *   Photo Object.
   * @param bool $nsfw
   *   Not safe for work flag.
   *
   * @return array
   *   Themed Photo.
   */
  public function preparePhoto($photo_obj, $nsfw = FALSE) {
    $size = $photo_obj->images[0]->size;
    $title = $photo_obj->name;

    // Image url can either be array or string.
    if (is_array($photo_obj->image_url)) {
      $img_url = $photo_obj->image_url[0];
    }
    else {
      $img_url = $photo_obj->image_url;
    }

    $photo_sizes = $this->photoGetSizes();
    $photo_size = $photo_sizes[$size];

    // $attributes['class'][] = 'd500px_photo_size_'. $size;
    // $attributes['class'][] = 'd500px_photo';
    // $attributes['class'] = implode(' ', $attributes['class']);.
    $photo = [
      '#theme' => 'image',
      '#style_name' => NULL,
      '#uri' => $img_url,
      '#alt' => $title,
      '#title' => $title,
      '#width' => $photo_size['height'],
      '#height' => $photo_size['width'],
      // '#attributes' => array('class' => $attributes['class']),.
    ];

    $blackout_nsfw = FALSE;

    // NSFW image logic.
    if ($photo_obj->nsfw == TRUE && $nsfw == FALSE) {
      $blackout_nsfw = TRUE;
      $classes[] = 'd500px-nsfw';
    }

    $classes[] = 'd500px-photo-size-' . $size;
    $class = implode(' ', $classes);

    $themed_photo = [
      '#theme' => 'd500px_photo',
      '#photo' => $photo,
      '#photo_page_url' => $photo_obj->photo_page_url,
      '#attributes' => ['class' => $class],
      '#blackout_nsfw' => $blackout_nsfw,
      '#attached' => [
        'library' => [
          'd500px/d500px.photos',
        ],
      ],
    ];

    return $themed_photo;
  }

  /**
   * Helper method to get available photo sizes.
   *
   * @return array
   *   Photo Sizes.
   */
  public function photoGetSizes() {
    $photo_sizes = [
      1 => ['height' => 70, 'width' => 70],
      2 => ['height' => 140, 'width' => 140],
      3 => ['height' => 280, 'width' => 280],
      100 => ['height' => 100, 'width' => 100],
      200 => ['height' => 200, 'width' => 200],
      440 => ['height' => 440, 'width' => 440],
      600 => ['height' => 600, 'width' => 600],
    ];

    return $photo_sizes;
  }

  /**
   * Helper method to get available features.
   *
   * @return array
   *   features Array.
   */
  public function availableFeatures() {
    $features = [
      'popular' => t('Popular Photos.'),
      'highest_rated' => t('Highest rated photos.'),
      'upcoming' => t('Upcoming photos.'),
      'editors' => t('Editors Choice.'),
      'fresh_today' => t('Fresh Today.'),
      'fresh_yesterday' => t('Fresh Yesterday.'),
      'fresh_week' => t('Fresh This Week.'),
      'user' => t('Photos by specified user.'),
      'user_friends' => t('Photos by users the specified user is following.'),
    ];

    return $features;
  }

  /**
   * Helper method to get available sort options.
   *
   * @return array
   *   Sort options array.
   */
  public function availableSortOptions() {
    $sort_options = [
      'created_at' => t('Time of upload, most recent first'),
      'rating' => t('Rating, highest rated first'),
      'times_viewed' => t('View count, most viewed first'),
      'votes_count' => t('Votes count, most voted first'),
      'favorites_count' => t('Favorites count, most favorited first'),
      'comments_count' => t('Comments count, most commented first'),
      'taken_at' => t('Metadata date, most recent first'),
    ];

    return $sort_options;
  }

  /**
   * Helper method to get available categories.
   *
   * @return array
   *   Categories.
   */
  public function availableCategories() {
    $categories = [
      '- All -' => '- All -',
      0 => 'Uncategorized',
      10 => 'Abstract',
      11 => 'Animals',
      5 => 'Black and White',
      1 => 'Celebrities',
      9 => 'City and Architecture',
      15 => 'Commercial',
      16 => 'Concert',
      20 => 'Family',
      14 => 'Fashion',
      2 => 'Film',
      24 => 'Fine Art',
      23 => 'Food',
      3 => 'Journalism',
      8 => 'Landscapes',
      12 => 'Macro',
      18 => 'Nature',
      4 => 'Nude',
      7 => 'People',
      19 => 'Performing Arts',
      17 => 'Sport',
      6 => 'Still Life',
      21 => 'Street',
      26 => 'Transportation',
      13 => 'Travel',
      22 => 'Underwater',
      27 => 'Urban Exploration',
      25 => 'Wedding',
    ];

    return $categories;
  }

}
