<?php

namespace Drupal\xpath_scraper_test\Controller;

/**
 * A service that provide xpath_scraper module with target website configuration.
 *
 * @todo add new features and fields examples.
 */
class ScraperConfigController {

  /**
   * The Target website.
   *
   * @var string.
   */
  public $target_website = 'http://www.EXAMPLE_WEBSITE.com';

  /**
   * The collection of data link.
   *
   * @var string.
   */
  public $website_collection_link = 'http://www.EXAMPLE_WEBSITE.com';

  /**
   * The collection links xpath.
   *
   * @var string.
   */
  public $collection_links_xpath = ''; // example '//h3/a'

  /**
   * The limite of pages that will scraping.
   *
   * @var string.
   */
  public $limit = 10; // 0 for unlimited.

  /**
   * The page structure and content type settings.
   *
   * @var array.
   */
  public $page = [];

  /**
   * Constructs a ScraperConfigController object.
   */
  public function __construct()
  {
  	$this->setPageConfig();
  }

  /**
   * Set page configuration to $page variable.
   */
  public function setPageConfig()
  {
  	// Content type
    $page['type'] = ''; // example 'article'
    
    // Fields:
    // You can add any number of fields here depend on content type.
    // Copy field configuration then edit name and xpath values.
    // Now we support three type of fields configuration, more features coming soon.
    
    // 1- Example of single field value configuration
    $page['fields'][0]['name'] = ''; // example 'title'
    $page['fields'][0]['multiple_values'] = false;
    $page['fields'][0]['xpath'] = ''; // example '//article//h1'
    $page['fields'][0]['dom_reference'] = 'nodeValue';
    
    // 2- Multiple field values
    $page['fields'][1]['name'] = 'body';
    $page['fields'][1]['multiple_values'] = true;
    $page['fields'][1]['format'] = 'basic_html';
    $page['fields'][1]['separator'] = '';
    $page['fields'][1]['xpath'] = ''; // example '//*[@id="divcont"]/article/div[3]/p'
    $page['fields'][1]['dom_reference'] = 'nodeValue';
    
    // 3- Media field configuration example
    $page['fields'][2]['name'] = ''; // example 'field_image'
    $page['fields'][2]['type'] = 'image';
    $page['fields'][2]['multiple_values'] = false;
    $page['fields'][2]['xpath'] = ''; // example '//article//*[@class="img-cont"]//img'
    $page['fields'][2]['dom_reference_attribute'] = ''; // example 'src'
    $page['fields'][2]['alt_xpath'] = ''; // example '//article//*[@class="img-cont"]//img'
    $page['fields'][2]['alt_dom_reference_attribute'] = ''; // example 'alt'
    $page['fields'][2]['title_xpath'] = ''; // example '//article//*[@class="img-cont"]//img'
    $page['fields'][2]['title_dom_reference_attribute'] = ''; // example 'title'

  	$this->page = $page;
  }

}