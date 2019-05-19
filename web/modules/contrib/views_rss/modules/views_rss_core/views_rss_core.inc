<?php

/**
 * @file
 * Preprocess functions for Views RSS: Core Elements module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\core\Url;
use Drupal\media_entity\Entity\Media;
use Drupal\image\Entity\ImageStyle;

/**
 * Preprocess function for channel <title> element.
 */
function views_rss_core_preprocess_channel_title(&$variables) {
  $config = \Drupal::config('system.site');

  if ($variables['view']->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $variables['view']->getTitle();
  }

  $variables['elements'][0]['value'] = strip_tags($title);
}

/**
 * Preprocess function for channel <description> element.
 */
function views_rss_core_preprocess_channel_description(&$variables) {
  if (empty($variables['elements'][0]['value'])) {
    $variables['elements'][0]['value'] = \Drupal::config('system.site')->get('slogan');
  }
}

/**
 * Preprocess function for channel <link> element.
 */
function views_rss_core_preprocess_channel_link(&$variables) {
  // The link element identifies the URL of the web site associated with
  // the feed (and not the feed's URL, as Drupal makes you think).
  // See http://www.rssboard.org/rss-profile#element-channel-link
  $variables['elements'][0]['value'] = $GLOBALS['base_url'] . '/';
}

/**
 * Preprocess function for channel <atom:link> element.
 *
 * @todo needs to work in all cases including where view args are changed
 */
function views_rss_core_preprocess_channel_atom_link(&$variables) {
  $url_options = array('absolute' => TRUE);
  $input = $variables['view']->getExposedInput();
  if ($input) {
    $url_options['query'] = $input;
  }

  // Use the Drupal path not the view path here due to possible exposed filters
  // changing the effective path for views.
  $variables['elements'][0]['attributes'] = array(
    'rel' => 'self',
    'href' => $GLOBALS['base_url'] . \Drupal::service('path.current')->getPath(),
  );
}

/**
 * Preprocess function for channel <language> element.
 */
function views_rss_core_preprocess_channel_language(&$variables) {
  if (empty($variables['elements'][0]['value'])) {
    $variables['elements'][0]['value'] = Html::escape(\Drupal::languageManager()->getCurrentLanguage()->getId());
  }
}

/**
 * Preprocess function for channel <category> element.
 */
function views_rss_core_preprocess_channel_category(&$variables) {
  // No value = no preprocessing.
  if (empty($variables['elements'][0]['value'])) {
    return;
  }
  $elements = array();
  $categories = explode(',', $variables['elements'][0]['value']);
  foreach ($categories as $category) {
    $elements[] = array(
      'key' => 'category',
      'value' => trim($category),
    );
  }
  $variables['elements'] = $elements;
}

/**
 * Preprocess function for channel <image> element.
 */
function views_rss_core_preprocess_channel_image(&$variables) {
  // No value = no preprocessing.
  if (empty($variables['elements'][0]['value'])) {
    return;
  }

  $image_path = $variables['elements'][0]['value'];
  // Get value of channel <title> element from its preprocess function.
  views_rss_core_preprocess_channel_title($variables);
  $title = $variables['elements'][0]['value'];
  // Create subelements array.
  $variables['elements'][0]['value'] = array(
    // The image's url element identifies the URL of the image.
    'url' => file_create_url($image_path),
    // The image's title element SHOULD have the same text as the channel's
    // title element and be suitable for use as the alt attribute of the img
    // tag in an HTML rendering.
    'title' => $title,
    // The image's link element identifies the URL of the web site represented
    // by the image (not the feed URL).
    'link' => $GLOBALS['base_url'] . '/',
  );
  // Try to get image description from website's mission.
  if ($site_slogan = \Drupal::config('system.site')->get('slogan')) {
    $variables['elements'][0]['value']['description'] = $site_slogan;
  }
  // Get image width and height.
  $image = Drupal::service('image.factory')->get($image_path);
  if (!empty($image)) {
    $variables['elements'][0]['value']['width'] = $image->getWidth();
    $variables['elements'][0]['value']['height'] = $image->getHeight();
  }
}

/**
 * Preprocess function for channel <pubDate> and <lastBuildDate> elements.
 *
 * It will return values for date element providing that original Views query
 * was modified appropriately by views_rss_core_views_query_alter() by adding
 * new fields to SELECT clause retrieving object creation (for <pubDate>)
 * or modification timestamp (for <lastBuildDate>).
 */
function views_rss_core_preprocess_channel_date(&$variables) {
  // @TODO make this function friendly for already formatted dates.
  if (count($variables['view']->result) > 0) {
    $max_date = 0;
    foreach ($variables['view']->result as $row) {
      $key = $variables['elements'][0]['key'];
      if (isset($row->$key) && $row->$key > $max_date) {
        $max_date = $row->$key;
      }
    }
    if ($max_date) {
      $variables['elements'][0]['value'] = date('r', $max_date);
    }
  }
}

/**
 * Preprocess function for channel <skipHours> and <skipDays> elements.
 */
function views_rss_core_preprocess_channel_skip(&$variables) {
  // No value = no preprocessing.
  if (empty($variables['elements'][0]['value'])) {
    return;
  }
  $elements = array();
  $skips = strip_tags($variables['elements'][0]['value']);
  if (!empty($skips)) {
    foreach (explode(',', $skips) as $skip_value) {
      $elements[] = array(
        'key' => ($variables['elements'][0]['key'] == 'skipHours') ? 'hour' : 'day',
        'value' => trim($skip_value),
      );
    }
  }
  $variables['elements'][0]['value'] = $elements;
}

/**
 * Preprocess function for channel <cloud> element.
 */
function views_rss_core_preprocess_channel_cloud(&$variables) {
  // No value = no preprocessing.
  if (empty($variables['elements'][0]['value'])) {
    return;
  }
  if ($url = parse_url($variables['elements'][0]['value'])) {
    $variables['elements'][0]['value'] = NULL;
    $variables['elements'][0]['attributes'] = array(
      'domain' => $url['host'],
      'port' => $url['port'],
      'path' => $url['path'],
      'registerProcedure' => $url['fragment'],
      'protocol' => $url['scheme'],
    );
  }
}

/**
 * Preprocess function for item <category> element.
 *
 * See http://www.rssboard.org/rss-profile#element-channel-item-category
 * for RSS Advisory Board requirements/recommendations.
 *
 * @see RSSCategoryFormatter::viewElements()
 */
function views_rss_core_preprocess_item_category(&$variables) {
  // No raw values = no preprocessing.
  if (empty($variables['raw']['items'])) {
    return;
  }

  $variables['elements'] = array();
  foreach ($variables['raw']['items'] as $item) {
    // When 'Content: <vocabulary_name>' was selected for source of category
    // element, we will get all we need in raw values.
    if (!empty($item['raw']) && is_object($item['raw']) && $item['raw']->target_id) {
      $term = $item['raw']->entity;
    }
    // On the other hand, when 'Content: All taxonomy terms' field was added
    // as a source for category element, we won't have raw values, instead
    // an array containing only tid, name, vocabulary_vid and vocabulary keys,
    // so we need to load full term object first before processing it further.
    elseif (!empty($item['tid'])) {
      $term = Term::load($item['tid']);
    }

    // According to RSS Advisory Board, the category's value should be
    // a slash-delimited string that identifies a hierarchical position
    // in the taxonomy.
    $values = array();
    // Load parent term objects (this includes original term as well).
    if ($parents = \Drupal::entityManager()->getStorage('taxonomy_term')->loadAllParents($term->id())) {
      foreach ($parents as $parent) {
        $values[] = $parent->label();
      }
    }

    $element = array(
      'key' => 'category',
      'value' => implode('/', array_reverse($values)),
      // Drupal uses term URL for domain attribute. RSS Best Practices say that
      // a domain attribute identifies the category's taxonomy - which suggests
      // either vocabulary name or its URL. We don't have any safe way to know
      // public vocabulary URL, could use its name instead though? @TODO?
      'attributes' => array(
        'domain' => $term->url('canonical', array('absolute' => TRUE)),
      ),
    );

    $variables['elements'][] = $element;
  }
}

/**
 * Preprocess function for item <enclosure> element.
 */
function views_rss_core_preprocess_item_enclosure(&$variables) {
  // No raw values = no file preprocessing.
  if (empty($variables['raw']['items'])) {
    return;
  }

  $variables['elements'] = array();
  foreach ($variables['raw']['items'] as $item) {
    // File fields.
    if (!empty($item['rendered']['#file'])) {
      $file = $item['rendered']['#file'];
    }
    // Image fields.
    if (!empty($item['rendered']['#item']->entity)) {
      $file = $item['rendered']['#item']->entity;
    }

    // Start building RSS element.
    $element = array(
      'key' => 'enclosure',
      'attributes' => array(),
    );

    // Handle the situation where we are dealing with an embedded YouTube video.
    $class = '\Drupal\video_embed_field\Plugin\Field\FieldType\VideoEmbedField';
    if ($item['raw'] instanceof $class) {
      $item['rendered']['#markup'] = $item['raw']->value;
      $element['attributes']['type'] = 'application/x-shockwave-flash';
      $element['attributes']['length'] = 'unknown';
    }

    // File entity found.
    if (!empty($file)) {
      // Image style is defined, need to link to resized version.
      if ($image_style_name = $item['rendered']['#image_style']) {
        $image_uri = $file->getFileUri();
        $image_style = entity_load('image_style', $image_style_name);
        $image_style_uri = $image_style->buildUri($image_uri);

        // If the derivative doesn't exist yet, we won't be able to get its size
        // to add it to the 'length' attribute, so we need to create it first.
        if (!file_exists($image_style_uri)) {
          $image_style->createDerivative($image_uri, $image_style_uri);
        }

        $element['attributes'] = array(
          'url' => $image_style->buildUrl($image_uri),
          'length' => filesize($image_style_uri),
          'type' => $file->getMimeType(),
        );
      }
      // Normal image size.
      else {
        $element['attributes'] = array(
          'url' => $file->url(),
          'length' => $file->getSize(),
          'type' => $file->getMimeType(),
        );
      }
    }
    // No file entity found, but something still was assigned to be displayed
    // as enclosure, so we just put its value in the url attribute.
    elseif (!empty($item['rendered']['#markup'])) {
      // Hack for using CDN files.
      // @todo make this more generalized to handle file and image entities.
      _convert_to_external_file($item['rendered']['#markup'], TRUE);

      $original_file = $item['rendered']['#markup'];

      _add_podtrac_redirect($item['rendered']['#markup']);

      $element['attributes']['url'] = $item['rendered']['#markup'];

      // Load the file length and type.
      $headers = get_headers($original_file, $format = 1);
      if (empty($element['attributes']['length']) && !empty($headers['Content-Length'])) {
        $element['attributes']['length'] = $headers['Content-Length'];
      }
      if (empty($element['attributes']['type']) && !empty($headers['Content-Type'])) {
        $element['attributes']['type'] = $headers['Content-Type'];
      }
    }

    $variables['elements'][] = $element;
  }
}

/**
 * Preprocess function for item <guid> element.
 */
function views_rss_core_preprocess_item_guid(&$variables) {
  // No value = no preprocessing.
  if (empty($variables['elements'][0]['value'])) {
    return;
  }
  // Hack for using CDN files.
  // @todo make this into a hook of some kind
  _convert_to_external_file($variables['elements'][0]['value']);

  $is_permalink = 'false';
  if (
    !empty($variables['item']['views_rss_core']['link'])
    && UrlHelper::isValid($variables['elements'][0]['value'], TRUE)
  ) {
    $is_permalink = 'true';
  }
  $variables['elements'][0]['attributes']['isPermaLink'] = $is_permalink;
}

/**
 * Preprocess function for item <source> element.
 */
function views_rss_core_preprocess_item_source(&$variables) {
  $config = \Drupal::config('system.site');

  // Allow override of title based on view->args settings.
  // @todo find a better way of overriding title
  if (isset($variables['view']->args['title'])) {
    $title = $variables['view']->args['title'];
  }
  elseif ($variables['view']->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $variables['view']->getTitle();
  }

  $url_options = array('absolute' => TRUE);
  $input = $variables['view']->getExposedInput();
  if ($input) {
    $url_options['query'] = $input;
  }

  // @todo handle all url situations including exposed input
  $variables['elements'][0]['value'] = strip_tags($title);
  $variables['elements'][0]['attributes'] = array('url' => $GLOBALS['base_url'] . \Drupal::service('path.current')->getPath(),);
}

/**
 * Preprocess function for item <description> element.
 * This adds an image to the <description> at the beginning.
 */
function views_rss_core_preprocess_item_description_image(&$variables) {

  $image_array = array();

  if (!empty($variables['raw']['items'])) {
    foreach ($variables['raw']['items'] as $item) {

      // Entity reference to media.
      $entity = Media::load($item['raw']->target_id);
      $file_uri = $entity->field_image->entity->getFileUri();

      $sized_image = ImageStyle::load('article_header_s_260px');
      $url = $sized_image->buildUrl($file_uri);

      $image_array = [
        'url' => $url,
      ];

      $file_uri = $sized_image->buildUri($file_uri);

      $image = \Drupal::service('image.factory')->get($file_uri);
      if ($image->isValid()) {
        $image_array['width'] = $image->getWidth();
        $image_array['height'] = $image->getHeight();
      }
    }
  }

  $variables = array();
  $variables['description_image'] = $image_array;

}

/**
 * Preprocess function for item <dc:creator> element.
 * This strips stray "Author" text leaving just Author 1, Author 2
 *
 * @todo make this more generalized, part of dc module, and a hook.
 */
function views_rss_core_preprocess_item_dc_creator(&$variables) {
  if (empty($variables['elements'][0]['value'])) {
    return;
  }

  $authors = $variables['elements'][0]['value'];

  $authors = str_replace('   Author', '', $authors);
  $author_array = explode(',', $authors);

  $elements = array();
  foreach ($author_array as $dc_created) {
    // Strip Role and everything after.
    $dc_created = preg_replace('/Role(.*)$/s', '', $dc_created);

    $elements[] = array(
      'key' => 'dc:creator',
      'value' => trim($dc_created),
    );
  }

  $variables['elements'] = $elements;
}

/**
 * Preprocess function for item <description> element.
 */
function views_rss_core_preprocess_item_description(&$variables) {
  //echo "DESCRIPTION " . $variables['elements'][0]['value'];
}

/**
 * Convert CDN internal name to external name.
 */
function _convert_to_external_file(&$filename, $force_http = TRUE) {

  // Strip /sites/ if it exists at the beginning of the filename.
  if (preg_match('/^\/sites\/\//', $filename)) {
    $filename = preg_replace('/^\/sites\//', '', $filename);
  }

  // If the filename begins with / then add the url.
  if (preg_match('/^\/[^\/]/', $filename)) {
    $filename = $GLOBALS['base_url'] . $filename;
  }

  // If the filename begins with // then add the scheme.
  if (preg_match('/^\/\//', $filename)) {
    $scheme = \Drupal::request()->getScheme();
    if ($force_http) {
      $scheme = 'http';
    }

    $filename = $scheme . ":" . $filename;
  }
}

/**
 * Convert CDN internal name to external name.
 */
function _add_podtrac_redirect(&$filename) {

  $filename = preg_replace('/^(http[s]?:\/\/)/', '$1dts.podtrac.com/redirect.mp3/', $filename);
}

/**
 * Preprocess function for item <link> element.
 */
function views_rss_core_preprocess_item_link(&$variables) {

  // Hack for using CDN files.
  // @todo make this more generalized to handle file and image entities.
  _convert_to_external_file($variables['elements'][0]['value']);

  return;
}

/**
 * Preprocess function for item <link> element.
 */
function views_rss_core_preprocess_item_author(&$variables) {
  // @TODO move this into hooks.
  if (empty($variables['elements'][0]['value'])) {
    return;
  }

  $authors = $variables['elements'][0]['value'];

  $authors = str_replace('Author', '', $authors);
  $author_array = explode(',', $authors);

  $elements = array();
  foreach ($author_array as $dc_created) {
    // Strip Role and everything after.
    $dc_created = preg_replace('/Role(.*)$/s', '', $dc_created);

    $elements[] = array(
      'key' => 'author',
      'value' => trim($dc_created),
    );

    // Just do one for now.
    break;
  }

  $variables['elements'] = $elements;
}