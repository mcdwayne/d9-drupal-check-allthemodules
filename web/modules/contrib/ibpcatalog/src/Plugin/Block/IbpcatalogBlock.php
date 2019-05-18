<?php

/**
 * @file
 * Contains \Drupal\ibpcatalog\Plugin\Block\IBPCatalogBlock.
 */

namespace Drupal\ibpcatalog\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Utility\String;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ParseException;

/**
 * Provides an 'IBP Catalog' block with the selected items from the feed.
 *
 * @Block(
 *  id = "ibpcatalog_block",
 *  admin_label = @Translation("ibpcatalog Block"),
 *  category = @Translation("Custom")
 * )
 */
class IBPCatalogBlock extends BlockBase {

  /**
   * IBP Catalog API URL.
   */
  const IBPCATALOG_API_URL = 'http://cat.internetbrokerproject.be/ibpcatalog/Feed/CatalogAtomFeed.svc/DigestedCatalogItems';

  /**
   * IBP Catalog Connection Timeout.
   */
  const IBPCATALOG_TIMEOUT = 2;

  /**
   * IBP Catalog Default Cache Duration.
   */
  const IBPCATALOG_CACHE_DEFAULT = 3600;

  /**
   * The catalog personal key.
   */
  protected $key;

  /**
   * The block selected language.
   */
  protected $language;

  /**
   * The block selected target.
   */
  protected $target;

  /**
   * The sub category action code like image, icon, banner...
   */
  protected $sub_category_key;

  /**
   * The block selected size.
   */
  protected $friendly_size;

  /**
   * The block selected product domain.
   */
  protected $product_domain;

  /**
   * The company selected target.
   */
  protected $company;

  /**
   * The limit of items to display.
   */
  protected $limit;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'language' => '',
      'target' => '',
      'sub_category_key' => '',
      'friendly_size' => '',
      'product_domain' => '',
      'company' => '',
      'limit' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    $form = parent::blockForm($form, $form_state);

    //Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    // Define block language.
    $form['language'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('All'),
        'FR' => t('French'),
        'NL' => t('Dutch'),
      ),
      '#title' => t('Language'),
      '#default_value' => isset($config['language']) ? $config['language'] : '',
    );

    // Define product target.
    $form['target'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('All'),
        'PERSONAL' => t('Personal'),
      ),
      '#title' => t('Target'),
      '#default_value' => isset($config['target']) ? $config['target'] : '',
    );

    // IBP SubCategoryKey.
    $form['sub_category_key'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('All'),
        'IBPSC-01' => t('Ordinary text'),
        'IBPSC-03' => t('Product icon'),
        'IBPSC-04' => t('Image'),
        'IBPSC-05' => t('Film'),
        'IBPSC-07' => t('Audio'),
        'IBPSC-08' => t('Full web page'),
        'IBPSC-09' => t('Static banner with link'),
        'IBPSC-10' => t('Dynamic banner with link'),
        'IBPSC-11' => t('Document (Pdf, Doc, Xls,...)'),
        'IBPSC-12' => t('Mini-website'),
        'IBPSC-15' => t('Banner without link, with fixed content'),
        'IBPSC-16' => t('Banner without link, with variable content'),
      ),
      '#title' => t('Sub Category'),
      '#default_value' => isset($config['sub_category_key']) ? $config['sub_category_key'] : '',
    );

    // Define block size.
    $form['friendly_size'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('All'),
        'IAB UAP Leaderboard (728 x 90)' => t('Leaderboard'),
        'IAB UAP Medium rectangle (300 x 250)' => t('Medium rectangle'),
        'IAB UAP Rectangle (180 x 150)' => t('Small rectangle'),
      ),
      '#title' => t('Friendly Size'),
      '#default_value' => isset($config['friendly_size']) ? $config['friendly_size'] : '',
    );

    // Define product domain.
    // IBP ProductDomainCode.
    $form['product_domain'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('All'),
        'X916-00' => t('No domain'),
        'X916-01' => t('Life and investments'),
        'X916-02' => t('Individual'),
        'X916-03' => t('Fire - basic risks'),
        'X916-04' => t('CL private individuals'),
        'X916-05' => t('Motor'),
        'X916-06' => t('Accidents at work and group insurance'),
        'X916-07' => t('CL other than private individuals'),
        'X916-08' => t('Objective and real estate liability'),
        'X916-09' => t('Legal assistance'),
        'X916-10' => t('Hospitalization and health care'),
        'X916-11' => t('Fire - special risks'),
        'X916-12' => t('Transport and Marine'),
        'X916-13' => t('Loan'),
        'X916-21' => t('Travel'),
        'X916-22' => t('Assistance'),
        'X916-23' => t('Investments and branches 23 and 26'),
        'X916-98' => t('Miscellaneous'),
        'X916-99' => t('Multi-domain'),
      ),
      '#title' => t('Product Domain'),
      '#default_value' => isset($config['product_domain']) ? $config['product_domain'] : '',
    );

    // Define company.
    $form['company'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => t('All'),
        '0097' => t('Allianz'),
        '0039' => t('AXA Belgium'),
      ),
      '#title' => t('Company'),
      '#default_value' => isset($config['company']) ? $config['company'] : '',
    );

    // Define block items limit.
    $form['limit'] = array(
      '#type' => 'number',
      '#title' => $this->t('Limit:'),
      '#description' => t('Leave 0 if you want all values'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => isset($config['limit']) ? $config['limit'] : '',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    // Save config.
    $this->setConfigurationValue('language', $form_state['values']['language']);
    $this->setConfigurationValue('target', $form_state['values']['target']);
    $this->setConfigurationValue('sub_category_key', $form_state['values']['sub_category_key']);
    $this->setConfigurationValue('friendly_size', $form_state['values']['friendly_size']);
    $this->setConfigurationValue('product_domain', $form_state['values']['product_domain']);
    $this->setConfigurationValue('company', $form_state['values']['company']);
    $this->setConfigurationValue('limit', $form_state['values']['limit']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get module configuration.
    $module_config = \Drupal::config('ibpcatalog.settings');
    $this->key = $module_config->get('key');

    // Get block configuration.
    $config = $this->getConfiguration();
    $this->language = $config['language'];
    $this->target = $config['target'];
    $this->sub_category_key = $config['sub_category_key'];
    $this->friendly_size = $config['friendly_size'];
    $this->product_domain = $config['product_domain'];
    $this->company = $config['company'];
    $this->limit = $config['limit'];

    // Create cache key.
    $cid = 'ibpcatalog::' . '::'
      . $this->language . '::'
      . $this->target . '::'
      . $this->sub_category_key . '::'
      . $this->friendly_size . '::'
      . $this->product_domain . '::'
      . $this->company;

    // Try to get block from the cache.
    if ($cache = \Drupal::cache()->get($cid)) {
      // Get cache.
      $items = $cache->data;
    }
    else {
      // Build cache.
      $items = $this->getItems();
      // Only store cache if valid.
      if ($items) {
        $expire = time() + self::IBPCATALOG_CACHE_DEFAULT;
        \Drupal::cache()->set($cid, $items, $expire);
      }
    }

    // Limit the number of items.
    if ($this->limit > 0) {
      $items = array_slice($items, 0, $this->limit);
    }

    // Shuffle the array.
    shuffle($items);

    return array(
      '#theme' => 'ibpcatalog',
      '#items' => $items,
      '#language' => $this->language,
      '#target' => $this->target,
      '#sub_category_key' => $this->sub_category_key,
      '#friendly_size' => $this->friendly_size,
      '#product_domain' => $this->product_domain,
      '#company' => $this->company,
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {

    // Create a HTTP client.
    $client = \Drupal::httpClient();

    // Set default options.
    $client->setDefaultOption('timeout', self::IBPCATALOG_TIMEOUT);

    // Create a GET request.
    $request = $client->createRequest('GET', self::IBPCATALOG_API_URL);

    // Filter on Key.
    if ($this->key != '') {
      $filter = "SecureGuid eq '" . $this->key . "'";
    } else {
      drupal_set_message('Set up you IBP Catalog key', 'status', TRUE);
      return FALSE;
    }

    // Filter on language.
    if ($this->language != '') {
      $filter .= " and Language eq '" . $this->language . "'";
    }

    // Filter on friendly_size.
    if ($this->friendly_size != '') {
      $filter .= " and FriendlySizeKey eq '" . $this->friendly_size . "'";
    }

    // Filter on product domain.
    if ($this->product_domain != '') {
      $filter .= " and ProductDomainCodeKey eq '" . $this->product_domain . "'";
    }

    // Filter on company.
    if ($this->company != '') {
      $filter .= " and CompanyCode eq '" . $this->company . "'";
    }

    // Filter on SubCategoryActionCode (icon, image, banner, document).
    if ($this->sub_category_key != '') {
      $filter .= " and SubCategoryKey eq '" . $this->sub_category_key . "'";
    }

    // Add a few query strings.
    $query = $request->getQuery();
    $query->set('$filter', $filter);

    // Make the HTTP request.
    try {
      $response = $client->send($request);
    } catch (RequestException $e) {
      return FALSE;
    }

    // If success.
    if ($response->getStatusCode() == 200) {
      // We are expecting XML content.
      try {
        // Convert the response into xml.
        $xml = $response->xml();
        // Convert xml into array.
        $items = $this->convertXML($xml);
        return $items;
      } catch (ParseException $e) {
        return FALSE;
      }
    }

    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function convertXML(\SimpleXMLElement $xml) {

    $properties = array(
      'CalculatedUrl',
      'Language',
      'Description',
      'DescriptionLong',
      'CompanyCode',
      'CompanyName',
      'ProductName',
      'DescriptionNL',
      'DescriptionLongNL',
      'DescriptionFR',
      'DescriptionLongFR',
      'ProductDomainCodeKey',
      'TargetKey',
      'CategoryKey',
      'CategoryDescription',
      'SubCategoryKey',
      'SubCategoryDescription',
      'DisplayModeKey',
      'FriendlySizeKey',
      'Width',
      'Height',
      'ProductValidity',
      'ProductVisibleFrom',
      'ProductVisibleUntil',
      'ItemValidity',
      'ItemVisibleFrom',
      'ItemVisibleUntil',
      'BrokerComments',
      'CatalogItemGuid',
      'ProductGuid',
      'CatalogGuid',
      'SecureGuid',
      'ProductDomainCodeDescriptionFR',
      'ProductDomainCodeDescriptionNL',
      'CategoryFR',
      'SubCategoryFR',
      'CompanyNameFR',
    );

    $compt = 0;
    foreach($xml->xpath('//m:properties') as $element) {
      $d = $element->children('http://schemas.microsoft.com/ado/2007/08/dataservices');
      foreach($properties as $propertie) {
        $items[$compt][strtolower($propertie)] = String::checkPlain($d->$propertie);
      }
      $compt++;
    }

    return $items;

  }

}
