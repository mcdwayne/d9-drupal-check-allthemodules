<?php

/**
 * @file
 * Functionality for the Epub module.
 */

use Drupal\Core\Url;

/**
 * Prepares variables for epub default templates.
 *
 * Default template: epub-formatter-default.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - download: link to download epub file.
 *   - iframe: iframe element.
 *   - pager: default pager.
 */
function template_preprocess_epub_formatter_default(&$variables) {
  $variables['#attached']['library'][] = 'epub/epub';

  $file = $variables['file'];
  $dir = 'public://epub_content/' . $file->id();
  $scan = file_scan_directory($dir, '/.*\.opf$/');
  $opf = array_shift($scan);
  $output = t('<p>This eBook can\'t be opened.</p>');
  if (isset($opf)) {
    $opfXML = simplexml_load_file($opf->uri);
    $opfXML->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
    $items = array();
    foreach ($opfXML->xpath('/opf:package/opf:spine/opf:itemref') as $key => $value) {
      $attributes = $value->attributes();
      $id = (string)$attributes['idref'];
      $items[$key] = file_create_url(epub_get_item($dir, $id));
    }
    $page = \Drupal::request()->query->get('page');
    if ($page) {
      $current_page = $page;
    }
    else {
      $current_page = 0;
    }
    $total = count($items);
    pager_default_initialize($total, 1);

    $iframe = array(
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#value' => '',
      '#attributes' => array(
        'class' => array('epub', 'ebook'),
        'id' => array($file->id()),
        'webkitallowfullscreen' => '',
        'mozallowfullscreen' => '',
        'allowfullscreen' => '',
        'name' => 'epub',
        'width' => $variables['width'] ? $variables['width'] : '100%',
        'height' => $variables['height'] ? $variables['height'] : '100%',
        'src' => $items[$current_page],
      ),
    );
    $output = render($iframe);
  }

  $file_url = file_create_url($file->getFileUri());
  $url = Url::fromUri($file_url);
  $download_link = \Drupal::l(t('Download'), $url);

  $variables['download'] = $download_link->getGeneratedLink();
  $variables['iframe'] = '<div id="ebook" class="epub">' . $output . '</div>';
  $variables['pager'] = array('#type' => 'pager');
}
