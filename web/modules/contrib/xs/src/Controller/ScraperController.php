<?php

namespace Drupal\xpath_scraper\Controller;

use Drupal\node\Entity\Node;

/**
 * A service for handling scrape a website data.
 *
 * @todo add new features.
 * @todo throw useful exceptions.
 */
class ScraperController implements ScraperInterface {

  /**
   * The configration of sub module.
   *
   * @var Object
   */
  protected $config;

  /**
   * Constructs a ScraperController object.
   *
   * @param object $config
   *   Load sub module configration.
   */
  public function __construct($config) {

    $this->config = $config;
  }

  /**
   * Scrape the collection page that contains pages links.
   *
   * Then Loop through links and call pathDetails with link as a parameter.
   */
  public function scrape() {
    // Get the html returned from the following url.
    $html = file_get_contents($this->config->website_collection_link);
    $doc = new \DOMDocument();
    // Disable libxml errors.
    libxml_use_internal_errors(TRUE);
    // If any html is actually returned.
    if (!empty($html)) {
      $doc->loadHTML($html);
      // Remove errors for yucky html.
      libxml_clear_errors();
      $xpath = new \DOMXPath($doc);
      // Get all anchors of items.
      $row = $xpath->query($this->config->collection_links_xpath);

      if ($row->length > 0) {
        foreach ($row as $key => $row) {
          $path = $this->config->target_website . implode('/', array_map('urlencode', explode('/', $row->getAttribute("href"))));
          if ($this->config->limit == 0 || $key < $this->config->limit) {
            $this->pathDetails($path);
          }
        }
      }

    }
  }

  /**
   * Get fields data by xpath from page.
   *
   * Then handle these fields and prepare content type.
   *
   * Save data as a node.
   *
   * @param string $path
   *   Page link.
   *
   * @return void return
   *   path.
   */
  public function pathDetails($path) {
    // Get the html returned from the following url.
    $html = file_get_contents($path);
    $doc = new \DOMDocument();
    // Disable libxml errors.
    libxml_use_internal_errors(TRUE);
    // If any html is actually returned.
    if (!empty($html)) {
      $doc->loadHTML($html);
      // Remove errors for yucky html.
      libxml_clear_errors();
      $xpath = new \DOMXPath($doc);
      // Set content type configration.
      $content_type['type'] = $this->config->page['type'];
      // Loop through fields.
      foreach ($this->config->page['fields'] as $field) {
        $field_value = '';
        $field_item = $xpath->query($field['xpath']);
        if ($field_item) {
          $field_xpath = $field_item->item(0);
          if ($field_xpath) {
            // Check for dom refrence.
            if (isset($field['dom_reference_attribute'])) {
              $field_value = $field_xpath->getAttribute($field['dom_reference_attribute']);
            }
            else {
              $field_value = $field_xpath->{$field['dom_reference']};
            }
          }
          // Check for multiple scaning.
          if ($field['multiple_values']) {
            // Loop.
            foreach ($field_item as $key => $content) {
              if ($key > 0 && isset($field['separator'])) {
                $field_value .= $field['separator'];
              }
              // Check for dom refrence.
              if (isset($field['dom_reference_attribute'])) {
                $field_value .= $content->getAttribute($field['dom_reference_attribute']);
              }
              else {
                $field_value .= $content->{$field['dom_reference']};
              }
            }
          }

          // Handle image field.
          if (isset($field['type']) && $field['type'] == 'image') {
            // Image src.
            $field_item = $xpath->query($field['xpath']);
            $image_xpath = $field_item->item(0);
            if ($image_xpath) {
              // Check for dom refrence.
              if (isset($field['dom_reference_attribute'])) {
                $image_src = $image_xpath->getAttribute($field['dom_reference_attribute']);
              }
              else {
                $image_src = $image_xpath->{$field['dom_reference']};
              }

              if ($image_src) {
                // Save image file.
                $data = file_get_contents($image_src);
                $file = file_save_data($data, 'public://' . basename($image_src), FILE_EXISTS_RENAME);
                // Store saved file id to image target_id.
                $content_type[$field['name']]['target_id'] = $file->id();
              }
            }

            // Image alt.
            $image_alt_item = $xpath->query($field['alt_xpath']);
            if ($image_alt_item) {
              $image_alt_xpath = $image_alt_item->item(0);
              if ($image_alt_xpath) {
                // Check for dom refrence.
                if (isset($field['alt_dom_reference_attribute'])) {
                  $image_alt = $image_alt_xpath->getAttribute($field['alt_dom_reference_attribute']);
                }
                else {
                  $image_alt = $image_alt_xpath->{$field['alt_dom_reference']};
                }
                if ($image_alt) {
                  // Store image alt.
                  $content_type[$field['name']]['alt'] = $image_alt;
                }
              }
            }

            // Image title.
            $image_title_item = $xpath->query($field['title_xpath']);
            if ($image_title_item) {
              $image_title_xpath = $image_title_item->item(0);
              if ($image_title_xpath) {
                // Check for dom refrence.
                if (isset($field['title_dom_reference_attribute'])) {
                  $image_title = $image_title_xpath->getAttribute($field['title_dom_reference_attribute']);
                }
                else {
                  $image_title = $image_title_xpath->{$field['title_dom_reference']};
                }
                if ($image_title) {
                  // Store image title.
                  $content_type[$field['name']]['title'] = $image_title;
                }
              }
            }

          }
          else {
            // Store general fields
            // Check for field format.
            if (isset($field['format'])) {
              $content_type[$field['name']]['value'] = $field_value;
              $content_type[$field['name']]['format'] = $field['format'];
            }
            else {
              $content_type[$field['name']] = $field_value;
            }
          }
        }
      }

      // Save node.
      $node = Node::create($content_type);
      $node->save();
    }
  }

}
