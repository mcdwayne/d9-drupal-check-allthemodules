<?php

namespace Drupal\content_parser\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\content_parser\Results;

/**
 * Defines the ContentParser entity.
 *
 * @ConfigEntityType(
 *   id = "content_parser",
 *   label = @Translation("ContentParser"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\content_parser\ContentParserListBuilder",
 *     "form" = {
 *       "add" = "Drupal\content_parser\Form\ContentParserForm",
 *       "edit" = "Drupal\content_parser\Form\ContentParserForm",
 *       "delete" = "Drupal\content_parser\Form\ContentParserDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\content_parser\ContentParserHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "content_parser",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/content_parser/{content_parser}",
 *     "add-form" = "/admin/structure/content_parser/add",
 *     "edit-form" = "/admin/structure/content_parser/{content_parser}/edit",
 *     "delete-form" = "/admin/structure/content_parser/{content_parser}/delete",
 *     "collection" = "/admin/structure/content_parser"
 *   }
 * )
 */
class ContentParser extends ConfigEntityBase {

  /**
   * The ContentParser ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The ContentParser label.
   *
   * @var string
   */
  protected $label;

  /**
   * The ContentParser start_url.
   *
   * @var string
   */
  protected $start_url;

  /**
   * The ContentParser test_url.
   *
   * @var string
   */
  protected $test_url;

  /**
   * The ContentParser check_code.
   *
   * @var string
   */
  protected $check_code;

  /**
   * The ContentParser depth.
   *
   * @var string
   */
  protected $depth;

  /**
   * The ContentParser white_list.
   *
   * @var string
   */
  protected $white_list;

  /**
   * The ContentParser entity_type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The ContentParser bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The ContentParser bundle.
   *
   * @var string
   */
  protected $codes;

  /**
   * The settings.
   *
   * @var string
   */
  protected $settings;

  /**
   * The settings.
   *
   * @var string
   */
  protected $results;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->results = new Results($this->id);
  }

  /**
   * {@inheritdoc}
   */
  public function getStartUrl() {
    return $this->start_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestUrl() {
    return $this->test_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckCode() {
    return $this->check_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepth() {
    return (int) $this->depth;
  }

  /**
   * {@inheritdoc}
   */
  public function getWhiteList() {
    return $this->white_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlackList() {
    return isset($this->black_list) ? $this->black_list : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectEntityType() {
    return $this->entity_type ? $this->entity_type : 'node';
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectEntityType($entity_type) {
    return $this->entity_type = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectEntityBundle($bundle) {
    return $this->bundle = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getCodes() {
    return $this->codes ? $this->codes : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCode($name) {
    return isset($this->codes[$name]['code']) ? $this->codes[$name]['code'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setResults(array $array) {
    return $this->results->setResults($array);
  }

  /**
   * {@inheritdoc}
   */
  public function generateResults() {
    return $this->results->generateResults();
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results->getResults();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    return isset($this->settings[$name]) ? $this->settings[$name] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function sleep() {
    $sleep = $this->getSetting('sleep');

    if ($sleep) {
      sleep($sleep);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStartUrls() {
    $start_urls = _get_array_from_text_lines($this->start_url);

    $urls = [];

    foreach ($start_urls as $start_url) {
      $start_url = trim($start_url);
      if (preg_match('#\[mask:(\d+),(\d+)\]#', $start_url, $matches)) {
        $min = (int) $matches[1];
        $max = (int) $matches[2];
        for ($i = $min; $i <= $max; $i++) {
          $urls[] = str_replace('[mask:' . $matches[1] . ',' . $matches[2] . ']', $i, $start_url);
        }
      }
      else {
        $urls[] = $start_url;
      }
    }

    return $urls;
  }


  /**
   * Return TRUE if URL is allowed.
   */
  public function findUrls($doc, $base_url) {
    $list = [];

    foreach (_parser_get_page_links($doc) as $url) {
      $link_url_absolute = parser_get_absolute_url($base_url, $url);

      if ($this->isAllowedUrl($link_url_absolute)) {
        $list[] = $link_url_absolute;
      }
    }

    return $list;
  }

  /**
   * Return TRUE if URL is allowed.
   */
  public function isAllowedUrl($absolute_url) {
    $start_urls = $this->getStartUrls();

    if (in_array($absolute_url, $start_urls)) {
      return true;
    }

    if ($this->white_list && !_content_parser_match_path($absolute_url, $this->white_list)) {
      return false;
    }

    if ($this->black_list && _content_parser_match_path($absolute_url, $this->black_list)) {
      return false;
    }

    if ($this->getSetting('only_this_domen')) {
      $url_host_allowed = FALSE;
      
      foreach ($start_urls as $start_url) {
        if (_parser_check_urls_host($start_url, $absolute_url)) {
          $url_host_allowed = TRUE;
          break;
        }
      }

      if (!$url_host_allowed) {
        return FALSE;
      }
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function getElements($doc) {
    if (!$this->getSetting('list_mode')) {
      return [$doc];
    }

    return $this->eval($doc, $this->getSetting('list_code'));
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityByRemoteId($remote_id) {
    return content_parser_get_entity_by_remote_id($remote_id);
  }

  /**
   * {@inheritdoc}
   */
  public function insertRemote($entity_type, $entity_id, $remote_id, $url) {
    if ($remote_id) {
      content_parser_insert_remote_id($entity_type, $entity_id, $remote_id, $url, $this->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadUrl($url, $headers = [], $cookieJar = null) {
    return _get_page_by_url($url, $headers, $cookieJar);
  }

  /**
   * {@inheritdoc}
   */
  public function getPhpQuery($html, $base_url) {
    return _content_parser_create_phpquery(
      $html,
      $this->getSetting('charset_fix')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function toAbsolutePath($url) {
    return parse_url($url)['path'];
  }

  /**
   * {@inheritdoc}
   */
  public function eval($doc, $code, $base_url = null) {
    return eval($code);
  }

  /**
   * {@inheritdoc}
   */
  public function evalEntity($doc, $entity, $code, $base_url) {
    return eval($code);
  }

  /**
   * {@inheritdoc}
   */
  public function evalInitCode() {
    $headers = [];
    $cookieJar = null;

    if ($init_code = $this->getSetting('init_code')) {
      eval($init_code);
    }

    return [
      'headers' => $headers,
      'cookieJar' => $cookieJar
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isCheck($doc, $base_url) {
    $code = $this->getCheckCode();

    if (!$code) {
      return true;
    }

    return $this->eval($doc, $code, $base_url);
  }

  /**
   * {@inheritdoc}
   */
  public function processElement($doc, $base_url) {
    if (!$this->isCheck($doc, $base_url)) {
      return $this->results->getNoAccessCode();
    }

    $remote_code = $this->getCode('remote_id');

    if ($remote_code) {
      $remote_id = $this->eval($doc, $remote_code, $base_url);
    }

    if ($remote_id) {
      $entity = $this->getEntityByRemoteId($remote_id);
    }

    if ($entity && $this->getSetting('no_update')) {
      return $this->results->getNoUpdateCode();
    }

    if (!$entity) {
      $entity = _entity_create($this->entity_type, $this->bundle);
    }

    if ($this->getSetting('save_url')) {
      $entity->set('path', [
        'alias' => $this->toAbsolutePath($base_url)
      ]);
    }

    foreach ($this->getCodes() as $field_name => $field) {
      $php_code = $this->getCode($field_name);

      if (!$php_code || $field_name == 'remote_id') {
        continue;
      }

      $result = $this->evalEntity(
        parser_download_images($doc, $base_url),
        $entity,
        $php_code,
        $base_url
      );

      $value = [];

      if ($field['isMulti'] && is_array($result)) {
        foreach ($result as $data) {
          if ($field['reference_create'] && $type = $field['reference']) {
            $value[] = [
              'target_id' => _reference_create($type, $data),
            ];
          } else {
            $value[] = $data;
          }
        }
      } elseif(!$field['isMulti']) {
        $value = $result;
      }

      if ($value) {
        $entity->set($field_name, $value);
      }
    }

    \Drupal::moduleHandler()
        ->invokeAll('content_parser_prepare_entity_' . $this->id, [$entity]);

    if ($prepare_code = $this->getSetting('prepare_code')) {
       $entity = $this->evalEntity($doc, $entity, $prepare_code, $base_url);
     }

    $is_new = $entity->isNew();

    try {
      $entity->save();
    } catch (\Exception $e) {
      return $this->results->getErrorCode();
    }

    if ($is_new) {
      $this->insertRemote($this->entity_type, $entity->id(), $remote_id, $base_url);
    }

    return !$is_new ? $this->results->getUpdateCode() : $this->results->getCreateCode();
  }

  /**
   * {@inheritdoc}
   */
  public function runTestUrl($base_url, $check_code) {
    $html = $this->loadUrl($base_url);

    if (!$html) {
      return 'Не удалось загрузить страницу';
    }

    $doc = $this->getPhpQuery($html, $base_url);

    if (!$doc) {
      return 'Не удалось прочитать страницу';
    }

    $doc = parser_download_images($doc, $base_url);

    try {
      return eval($check_code);
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }
}
