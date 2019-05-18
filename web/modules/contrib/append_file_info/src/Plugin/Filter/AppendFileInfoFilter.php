<?php

namespace Drupal\append_file_info\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;

/**
 * Provides the Append File Info filter.
 *
 * @Filter(
 *   id = "append_file_info_filter",
 *   title = @Translation("Append File Info Filter"),
 *   description = @Translation("Appends file info (extension, size) to links to local managed files."),
 *   settings = {
 *     "title" = TRUE,
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 * )
 */
class AppendFileInfoFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $dom = Html::load($text);
    foreach ($dom->getElementsByTagName('a') as $link) {
      $href = $link->getAttribute('href');
      preg_match('`^' . preg_quote(base_path(), '`') . '(.+)`', $href, $matches);
      if (!$matches) {
        continue;
      }
      $path = rawurldecode($matches[1]);
      $path = $this->stripLangcode($path, $langcode);
      $file = $this->getFileFromPath($path);
      if (!empty($file)) {
        $class = $link->getAttribute('class');
        // Skip file if it has a no-file-info class.
        if (!empty($class) && strstr('no-file-info', $class)) {
          continue;
        }
        // Skip link if it has images in it.
        foreach ($link->getElementsByTagName('img') as $img) {
          // Process next link.
          continue 2;
        }
        // Add file info.
        $within_text = _append_file_info_get_extra_link_text($file);
        $within = new \DOMText($within_text);

        $link->appendChild($within);

        // Add a wrapper span.
        $new_span = $dom->createElement('span');
        $mime_type = $file->getMimeType();
        // Add file icon.
        $new_span->setAttribute('class', 'file-with-file-info file file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']) . ' file--' . file_icon_class($mime_type));

        // Replace link with this wrapper span.
        $link->parentNode->replaceChild($new_span, $link);
        // Append this link to wrapper span.
        $new_span->appendChild($link);
      }
    }
    $html = Html::serialize($dom);
    return new FilterProcessResult($html);
  }

  /**
   * Strips the langcode prefix from the path.
   *
   * @param string $path
   *   The path which may have a prefix or not.
   *
   * @return string
   *   The path, without the prefix.
   */
  protected function stripLangcode($path) {
    $languages = \Drupal::languageManager()->getLanguages();
    $config = \Drupal::configFactory()->getEditable('language.negotiation');
    $prefixes = $config->get('url.prefixes');
    if ($languages) {
      $args = explode('/', $path);
      $prefix = array_shift($args);
      foreach ($languages as $langcode => $language) {
        if (isset($prefixes[$langcode]) && ($prefixes[$langcode] == $prefix)) {
          // Override path with shifted prefix.
          $path = implode('/', $args);
          break;
        }
      }
    }

    return $path;
  }

  /**
   * Gets file entity object from path.
   *
   * @param string $path
   *   The path to the file.
   *
   * @return object|bool
   *   File entity object or FALSE.
   */
  protected function getFileFromPath($path) {
    if (preg_match('`^' . preg_quote(PublicStream::basePath(), '`') . '/(.+)$`', $path, $matches)) {
      $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => 'public://' . $matches[1]]);
    }
    elseif (preg_match('`^' . preg_quote(PrivateStream::basePath(), '`') . '/(.+)$`', $path, $matches)) {
      $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => 'private://' . $matches[1]]);
    }
    elseif (preg_match('`^file/(\d+)`', $path, $matches)) {
      $fid = $matches[1];
      $files = File::loadMultiple([$fid]);
    }

    if (empty($files)) {
      return FALSE;
    }
    $file = reset($files);
    // Check whether file is local.
    $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri());
    if (!$wrapper instanceof LocalStream) {
      return FALSE;
    }
    return $file;
  }

}
