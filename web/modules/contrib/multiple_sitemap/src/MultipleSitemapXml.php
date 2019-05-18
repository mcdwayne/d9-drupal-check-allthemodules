<?php

/**
 * @file
 * Contains the function for  multiple sitemap xml files.
 */

namespace Drupal\multiple_sitemap;

use Drupal\Core\Url;
use Drupal\multiple_sitemap\MultipleSitemapDB;

class MultipleSitemapXml {

  private $dbObject;

  public function __construct()
  {
     $this->dbObject = new MultipleSitemapDB();
  }

  /**
   * Create xml sitemap.
   */
  public function multiple_sitemap_create_xml_sitemap() {
    // Create main sitemap xml file.First we have to get all files name.
    $filesnames = $this->dbObject->multiple_sitemap_get_files_name();
    $this->multiple_sitemap_create_main_file($filesnames);

    // Create all sub files.
    foreach ($filesnames as $ms_id => $filesname) {
      $this->multiple_sitemap_create_sub_file($ms_id, $filesname);
    }
  }

  /**
   * Create main xml sitemap file.
   *
   * @param array $filesnames
   *   Having file names.
   */
  public function multiple_sitemap_create_main_file($filesnames = array()) {

    $dir_name = 'public://multiple_sitemap';
    if (!file_exists($dir_name)) {
      mkdir($dir_name);
    }

    $file_name = $dir_name . '/sitemap.xml';
    if (file_exists($file_name)) {
      unlink($file_name);
    }

    $file = fopen($file_name, "w") or die("Unable to open file!");

    $lastmod = date('Y-m-d');

    $writer = new \XMLWriter();
    $writer->openURI($file_name);
    $writer->startDocument('1.0', 'UTF-8');
    $writer->setIndent(4);
    $writer->startElement('sitemapindex');
    $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    // Entries of sub files.
    if (!empty($filesnames)) {
      foreach ($filesnames as $filesname) {
        $sub_file_name = $dir_name . '/' . $filesname . '.xml';
        $sub_file_name = file_create_url($sub_file_name);
        $writer->startElement('sitemap');
        $writer->writeElement('loc', $sub_file_name);
        $writer->writeElement('lastmod', $lastmod);
        $writer->endElement();
      }
    }
    $writer->endElement();
    $writer->endDocument();
    $writer->flush();

    fclose($file);
  }

  /**
   * Create sub files.
   *
   * @param int $ms_id
   *   File ms id.
   * @param string $filesname
   *   File name.
   */
  public function multiple_sitemap_create_sub_file($ms_id, $filesname) {

    $total_links = $this->multiple_sitemap_get_sub_file_links($ms_id);

    if (!empty($total_links)) {
      $dir_name = 'public://multiple_sitemap';
      if (!file_exists($dir_name)) {
        mkdir($dir_name);
      }

      $file_name = $dir_name . '/' . $filesname . '.xml';
      if (file_exists($file_name)) {
        unlink($file_name);
      }

      $file = fopen($file_name, "w") or die("Unable to open file!");

      $lastmod = date('Y-m-d');

      $writer = new \XMLWriter();
      $writer->openURI($file_name);
      $writer->startDocument('1.0', 'UTF-8');
      $writer->setIndent(4);
      $writer->startElement('urlset');
      $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
      // Entries of sub files.
      if (!empty($total_links)) {
        foreach ($total_links as $total_link) {
          $writer->startElement('url');
          $writer->writeElement('loc', $total_link['link']);
          $writer->writeElement('changefreq', $total_link['changefreq']);
          $writer->writeElement('priority', $total_link['priority']);
          $writer->writeElement('lastmod', $lastmod);
          $writer->endElement();
        }
      }
      $writer->endElement();
      $writer->endDocument();
      $writer->flush();

      fclose($file);
    }
  }

  /**
   * Get links for a subfile.
   *
   * @param int $ms_id
   *   Multiple sitemap id.
   *
   * @return array
   *   Having total links for sub file.
   */
  public function multiple_sitemap_get_sub_file_links($ms_id) {

    // Get record for a file.
    $records = $this->dbObject->multiple_sitemap_get_record($ms_id);

    // Get custom links.
    $custom_links = !empty($records['custom_links']) ? $records['custom_links'] : NULL;

    $links = array();
    if (!is_null($custom_links)) {

      $custom_links = explode(',', $custom_links);

      foreach ($custom_links as $key => $custom_link) {
        $custom_link = '/' . $custom_link;
        $path = \Drupal::service('path.alias_manager')->getAliasByPath($custom_link);
        $path = Url::fromUserInput($path)->setAbsolute()->toString();
        $links[$key]['link'] = $path;
        $links[$key]['priority'] = '0.5';
        $links[$key]['changefreq'] = 'monthly';
      }
    }

    // Get content types links.
    $contents = !empty($records['content']) ? $records['content'] : array();
    $content_links = $this->dbObject->multiple_sitemap_get_content_links($contents);

    // Get Menus links.
    $menus = !empty($records['menu']) ? $records['menu'] : array();
    $menu_links = $this->dbObject->multiple_sitemap_get_menu_links($menus);

    // Get Vocab links.
    $vocabs = !empty($records['vocab']) ? $records['vocab'] : array();
    $vocab_links = $this->dbObject->multiple_sitemap_get_vocab_links($vocabs);

    $total_links = array_merge($links, $content_links);
    $total_links = array_merge($total_links, $menu_links);
    $total_links = array_merge($total_links, $vocab_links);

    $total_links = array_unique($total_links, SORT_REGULAR);

    $usedVals = array();
    $outArray = array();
    foreach ($total_links as $arrayItem) {
      if (!in_array($arrayItem['link'], $usedVals)) {
        $outArray[] = $arrayItem;
        $usedVals[] = $arrayItem['link'];
      }
    }

    return $outArray;
  }
}
