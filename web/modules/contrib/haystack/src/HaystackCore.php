<?php

namespace Drupal\haystack;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use GuzzleHttp\Exception\RequestException;

/**
 * Class HaystackCore.
 *
 * @package Drupal\haystack
 */
class HaystackCore {

  /**
   * Config Service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Haystack Settings.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * HaystackCore constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config Service.
   */
  public function __construct(ConfigFactory $config) {
    $this->config = $config;
    $this->settings = $config->getEditable('haystack.settings');
  }

  /**
   * Getter method for settings.
   *
   * @param string $key
   *   Setting key.
   * @param bool $else
   *   Default value.
   *
   * @return array|bool|mixed|null
   *   The setting value.
   */
  public function getSetting($key, $else = FALSE) {
    $ret = $this->settings->get($key);
    return (!$ret ? $else : $ret);
  }

  /**
   * Keeping track of processed item count.
   *
   * @param int $count
   *   The amount to increment the counter by.
   */
  public function incrementMeterPos($count = 1) {
    $pos = $this->getSetting('process_pos', 0);
    $total = $this->getSetting('process_total', 0);
    $pos += $count;
    $this->setSetting('process_pos', $pos);
    if ($pos == $total) {
      $this->resetMeterTotal();
    }
  }

  /**
   * Set the total count.
   *
   * @param int $add
   *   Value to set the meter to.
   */
  public function addMeterTotal($add) {
    $total = $this->getSetting('process_total', 0);
    $total += $add;
    $this->setSetting('process_total', $total);
  }

  /**
   * Reset the counter.
   */
  public function resetMeterTotal() {
    $this->setSetting('process_pos', 0);
    $this->setSetting('process_total', 0);
  }

  /**
   * Setter method for settings.
   *
   * @param string $key
   *   The key for the setting.
   * @param mixed $value
   *   The value to save.
   */
  public function setSetting($key, $value) {
    $this->settings->set($key, $value);
    $this->settings->save();
  }

  /**
   * Get content type definitions.
   *
   * @param bool $all
   *   Determine to return all available or just the ones enabled.
   *
   * @return array
   *   Array of content types.
   */
  public function getContentTypes($all = FALSE) {
    if ($all) {
      $options = [];
      foreach (NodeType::loadMultiple() as $type) {
        /** @var \Drupal\node\NodeTypeInterface $type */
        $options[$type->get('type')] = $type->get('name');
      }

      return $options;

    }
    else {
      return array_filter($this->getSetting('content_types', []));
    }
  }

  /**
   * Get credentials.
   *
   * @param string $apiKey
   *   The API key.
   *
   * @return bool
   *   Returns true if the site can connect to the Haystack server.
   */
  public function getCredentials($apiKey) {
    $uri = HAYSTACK_API_SERVER . HAYSTACK_API_VERSION . '/credentials?api_token=' . $apiKey;
    if ($response = @file_get_contents($uri)) {
      $data = json_decode($response);

      if (isset($data->status) && $data->status == 'success') {
        $this->setSetting('client_hash', $data->siteHash);
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Config for the status banner.
   */
  public function configBanner() {
    $url = 'admin/config/search/haystack';
    if ($this->healthCheck() < 2) {
      if (Url::fromRoute('<current>')->toString() != $url) {
        drupal_set_message(
          t('Please see the Haystack <a href="!url">configuration page</a> in order to complete the installation of this module.',
            ['!url' => Url::fromRoute('haystack.settings')->toString()]
          ), 'warning');
      }
    }
  }

  /**
   * Get the menu title of a node.
   *
   * If a menu link is created for the node we add that title to the index.
   *
   * @param int $nid
   *   Node ID.
   * @param string $lang
   *   Language code.
   *
   * @return string
   *   Title of the menu item.
   */
  public function getMenuTitle($nid, $lang = 'en') {

    $result = \Drupal::database()->query(
      "SELECT title FROM {menu_link_content_data}
          WHERE link__uri LIKE :link AND langcode = :lang LIMIT 1",
      [':link' => '%node/' . $nid, ':lang' => $lang]);

    $title = $result->fetchField();

    return empty($title) ? '' : $title;
  }

  /**
   * Get the thmubnail image to be used for the search result.
   *
   * Checks if an image is associated with the node and sets the first one it
   * finds as the thmubnail for results.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object to check.
   *
   * @return string
   *   The path to the found image.
   */
  public function getImage(Node $node) {
    $images = [];
    $modules = \Drupal::moduleHandler()
      ->getImplementations('haystack_get_image');
    foreach ($modules as $module) {
      $image = \Drupal::moduleHandler()
        ->invoke($module, 'haystack_get_image', [$node]);
      if (!empty($image)) {
        $images[$image['weight']] = $image['image'];
      }
    }

    $imagePath = '';
    if (count($images)) {
      $values = array_values($images);
      $imagePath = array_shift($values);
    }
    return $imagePath;
  }

  /**
   * Finds all tags associated with the node.
   *
   * @param int $nid
   *   The ID of the node to check.
   *
   * @return string
   *   The tags for the node, separated by commas.
   */
  public function getTags($nid) {
    $result = \Drupal::database()->query("SELECT ti.tid, td.name FROM {taxonomy_index} ti
      JOIN {taxonomy_term_field_data} td ON ti.tid = td.tid
      WHERE ti.nid = :nid", [':nid' => $nid]);
    $tags = $result->fetchAllKeyed();

    $tagArray = [];
    foreach ($tags as $tag) {
      $tagArray[] = $tag;
    }

    $list = count($tagArray) ? implode(', ', $tagArray) : '';

    return $list;
  }

  /**
   * Get all nodes of a certain type.
   *
   * @param string $type
   *   The node type.
   *
   * @return mixed
   *   Array of node IDs.
   */
  public function getNodes($type) {
    $nids = \Drupal::database()->query("SELECT nid FROM {node_field_data} WHERE status = 1 AND type = :type",
      [':type' => $type])
      ->fetchCol();

    return $nids;
  }

  /**
   * Get og attributes.
   *
   * Curls a page and filters out.
   * TODO: Currently returns empty because of HTML5 issue of DOMDocument.
   *
   * @param string $url
   *   The URL to crawl.
   *
   * @return array
   *   Array of scraped data.
   */
  public function getMeta($url) {
    return [
      'title' => '',
      'description' => '',
      'image' => '',
    ];

    //    $html = @file_get_contents($url);
    //    if ($html === FALSE) {
    //      //drupal_set_message(t('Haystack was unable to crawl for meta tags information on the following URL due to a lack of permission: !url', array('!url' => $url)), 'warning');
    //    }
    //    else {
    //      $doc = new DOMDocument();
    //      $doc->loadHTML($html);
    //
    //      $metanodes = $doc->getElementsByTagName('meta');
    //      foreach ($metanodes as $node) {
    //        $key = $node->getAttribute('property');
    //        $val = $node->getAttribute('content');
    //        if (!empty($key)) {
    //          $mdata[$key] = $val;
    //        }
    //      }
    //    }
    //
    //    return array(
    //      'title'       => isset($mdata['og:title']) ? $mdata['og:title'] : '',
    //      'description' => isset($mdata['og:description']) ? $mdata['og:description'] : '',
    //      'image'       => isset($mdata['og:image']) ? $mdata['og:image'] : ''
    //    );
  }

  /**
   * Health check.
   *
   * @return int
   *   Status count.
   */
  public function healthCheck() {

    $i = 0;

    if ($this->settings->get('api_key') && $this->settings->get('client_hash')) {
      $i++;
    }
    if ($this->settings->get('content_types') && $this->settings->get('first_index')) {
      $i++;
    }

    return $i;
  }

  /**
   * Get data from a menu item.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $link
   *
   *
   * @return array
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getLinkData(MenuLinkContent $link) {
    $url = Url::fromUri($link->get('link')->first()->getValue()['uri'])
      ->setAbsolute()
      ->toString();

    $og_tags = $this->getMeta($url);
    $package = [
      'api_token' => $this->getSetting('api_key'),
      'id' => 'menu-' . $link->id(),
      'es_type' => 'menu',
      'type' => '<type>Page</type>',
      'title' => empty($og_tags['title']) ? $link->getTitle() : $og_tags['title'],
      'link' => $url,
      'menu' => $link->getTitle(),
      'body' => empty($og_tags['description']) ?
        (!empty($link->get('description')->first()) ? $link->get('description')
          ->first()
          ->getValue()['value'] : '') :
        $og_tags['description'],
      'image' => $og_tags['image'],
      'tags' => '',
    ];

    return $package;
  }


  // TODO: Stats are not send currently


  /**
   * @param $package
   * @param string $type
   * @param string $op
   *
   * @return bool|mixed|\Psr\Http\Message\ResponseInterface|string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function apiCall($package, $type = 'index', $op = 'insert') {
    $readOnly = $this->getSetting('dev_mode');
    if ($readOnly) {
      return FALSE;
    }

    $result = '';

    $client = new \GuzzleHttp\Client();
    $url = HAYSTACK_API_SERVER . HAYSTACK_API_VERSION; // . '/' . $type;
    $token = '?api_token=' . $package['api_token'];

    if ($op == 'delete') {
      $url .= '/index/' . $package['type'] . '/' . $package['id'] . $token;
      $result = $client->delete($url);
    }
    elseif ($op == 'delete_all') { //We need to keep the index alive
      $url .= '/flush/index' . $token;
      $result = $client->delete($url);
    }
    elseif ($op == 'delete_type') { //We clear only one type
      $url .= '/flush/type/' . $type . $token;
      $client->delete($url);
    }
    elseif ($op == 'stats') { //We clear only one type
      //      $url .= '/stats'; //.$token;
      //      $data = json_encode($package, TRUE);
      //      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      //        'Content-Type: application/json',
      //        'Content-Length: ' . drupal_strlen($data)
      //      ));
      //      $package = array();
    }
    else {
      $url .= '/' . $type;
      try {
        $result = $client->request('POST', $url, [
          'form_params' => $package,
          'auth' => NULL,
        ]);
        return $result;
      } catch (\Exception $e) {
        \Drupal::logger('haystack')->error($e->getMessage());
      }
    }
    return $result;
  }

  // TODO: Implement Functionality

  /**
   * @return array
   */
  public function searchStatus() {
    //  // @FIXME
    //// Could not extract the default value because it is either indeterminate, or
    //// not scalar. You'll need to provide a default value in
    //// config/install/haystack.settings.yml and config/schema/haystack.schema.yml.
    //$types = \Drupal::config('haystack.settings')->get('haystack_content_types');
    //  // @FIXME
    //// Could not extract the default value because it is either indeterminate, or
    //// not scalar. You'll need to provide a default value in
    //// config/install/haystack.settings.yml and config/schema/haystack.schema.yml.
    //$menus = \Drupal::config('haystack.settings')->get('haystack_menus');
    //  foreach ($types as $k => &$v) {
    //    if ($v == '0') {
    //      unset($types[$k]);
    //    }
    //  }
    //  foreach ($menus as $k => &$v) {
    //    if ($v == '0') {
    //      unset($menus[$k]);
    //    }
    //  }
    //
    //  $total      = db_query('SELECT COUNT(*) FROM {node} WHERE status = 1 AND type IN (:types)',array(':types' => implode(', ', array_keys($types))))->fetchField();
    //  $menu_total = db_query('SELECT COUNT(*) FROM {menu_links} WHERE menu_name IN (:menus) AND hidden = 0',array(':menus' => implode(', ', array_keys($menus))))->fetchField();
    //
    //  $remaining  = db_query("SELECT COUNT(*) FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE n.status = 1 AND n.type IN (:types) AND d.sid IS NULL OR d.reindex <> 0",array(':types' => implode(', ', array_keys($types))))->fetchField();
    //
    //  //This is in place to check in case the user checks, unchecks. Will count requests to send through CRON + menus
    //  $total += $menu_total;
    //  if ($total < $remaining) {
    //    $total = $remaining + $menu_total;
    //  }
    return [
      'remaining' => 0,//$remaining,
      'total' => 0,//$total,
    ]; //Counts menu total in form encouraging results
    //}
  }

  /**
   * @param $menus
   * @param bool $delete
   *
   * @return bool
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function indexMenus($menus, $delete = FALSE) {
    if (![$menus]) {
      return FALSE;
    }

    // Use only those links that are not connected with a node to avoid duplication.
    $result = \Drupal::database()->query("SELECT * FROM {menu_link_content_data} WHERE menu_name IN (:menus[]) AND enabled = 1 AND link__uri NOT LIKE '%node/%'",
      [':menus[]' => $menus]);

    $api_token = $this->getSetting('api_key');
    $menu_items = [];
    $menu_cache = $this->getSetting('menu_cache');

    if ($delete) {
      foreach ($result as $record) {
        $menu_items['menu-' . $record->id] = [
          'api_token' => $api_token,
          'id' => 'menu-' . $record->id,
          'type' => 'menu',
        ];

        $this->apiCall($menu_items['menu-' . $record->id], 'index', 'delete');
      }
      $menu_cache = array_diff($menu_cache, array_keys($menu_items));
      $this->setSetting('menu_cache', $menu_cache);

    }
    else {
      foreach ($result as $record) {
        $attr = unserialize($record->link__options);
        $url = Url::fromUri($record->link__uri)->setAbsolute()->toString();
        $og_tags = $this->getMeta($url);

        $menu_items['menu-' . $record->id] = [
          'api_token' => $api_token,
          'id' => 'menu-' . $record->id,
          'type' => '<type>Page</type>',
          'es_type' => 'menu',
          'title' => empty($og_tags['title']) ? $record->title : $og_tags['title'],
          'link' => $url,
          'menu' => $record->title,
          'body' => empty($og_tags['description']) ? (isset($attr['title']) ? $attr['title'] : 'Menu item') : $og_tags['description'],
          'image' => $og_tags['image'],
          'tags' => '',
        ];

        $this->apiCall($menu_items['menu-' . $record->id]);

        $menu_cache[] = $record->id;
      }
      $this->setSetting('menu_cache', $menu_cache);

    }

    return TRUE;
  }

  /**
   * Get number of total items.
   *
   * @return int
   */
  public function haystackTotalItems() {
    $total = 0;
    $types = $this->getContentTypes();

    $menus = $this->getSetting('menus');
    $menu_total = \Drupal::database()->query("SELECT * FROM {menu_link_content_data} WHERE menu_name IN (:menus[]) AND enabled = 1 AND link__uri NOT LIKE '%node/%'",
      [':menus[]' => $menus]);

    $menu_total = intval($menu_total->fetchField());
    $total += $menu_total;

    foreach ($types as $t) {
      $total += count($this->getNodes($t));
    }

    return $total;
  }
}
