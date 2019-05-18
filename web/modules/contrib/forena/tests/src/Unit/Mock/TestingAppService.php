<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 1/31/16
 * Time: 8:48 PM
 */

namespace Drupal\Tests\forena\Unit\Mock;


use Drupal\forena\AppService;
use Drupal\forena\Form\ParameterForm;
use Drupal\forena\FrxPlugin\Renderer\FrxMenu;

class TestingAppService extends AppService {
  public $language = 'en';
  public $default_language = 'en';
  public $data_directory = '/tmp';
  public $has_access = TRUE;

  // Data Pathways.
  public $reportDirectory;
  public $reportIncludes;
  public $siteContext;
  public $modulePath;
  public $currentPath = 'reports/test';
  /** @var  ParameterForm */
  public $parameterForm;

  public $form_state;



  /**
   * Return Current site context.
   * @return array
   */
  public function __construct() {
    $this->modulePath = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
    $path = $this->modulePath;
    $site = [];
    $site['base_path'] = '/';
    $site['dir'] = '';
    $site['base_url'] = 'http://example.com';
    $site['user_name'] = 'testuser';
    $site['uid'] = 1;
    $site['language'] = $this->language;
    $this->siteContext = $site;
    $this->default_skin = 'default';
    // Determine module installation path based on current code
    $this->reportDirectory = "$path/tests/reports_overriden";
    $this->reportIncludes = ["$path/tests/reports"];
  }

  /*
   * Mock alter function.
   */
  public function alter($hook, &$var1, $var2 = NULL) {

  }

  /**
   * @return bool
   *   Fake out access check.
   */
  public function access($right) {
    return $this->has_access;
  }

  public function getRendererPlugins() {
    return [
      'FrxAjax' => '\Drupalforena\FrxPlugin\Renderer\FrxAjax', 
      'FrxCrosstab' => '\Drupal\forena\FrxPlugin\Renderer\FrxCrosstab',
      'FrxInclude' => '\Drupal\forena\FrxPlugin\Renderer\FrxInclude',
      'FrxMyReports' => '\Drupal\forena\FrxPlugin\Renderer\FrxMyReports',
      'FrxMenu' => FrxMenu::class,
      'FrxParameterForm' => '\Drupal\forena\FrxPlugin\Renderer\FrxParameterForm',
      'FrxSource' => '\Drupal\forena\FrxPlugin\Renderer\FrxSource',
      'FrxSVGGraph' => '\Drupal\forena\FrxPlugin\Renderer\FrxSVGGraph',
      'FrxTemplate' => '\Drupal\forena\FrxPlugin\Renderer\FrxTemplate',
      'FrxTitle' => '\Drupal\forena\FrxPlugin\Renderer\FrxTitle',
      'FrxXML' => '\Drupal\forena\FrxPlugin\Renderer\FrxXML',
      'RendererBase' => '\Drupal\forena\FrxPlugin\Renderer\RendererBase',
    ];
  }

  public function getAjaxPlugins() {
    return [
      'invoke' => '\Drupal\forena\FrxPlugin\AjaxCommand\Invoke',
    ];
  }

  public function getContextPlugins() {
    return [
      'custom_security' => 'Drupal\Tests\forena\Unit\FrxPlugin\Context\CustomSecurity',
      'FrxReport' => 'Drupal\forena\FrxPlugin\Context\FrxReport', 
    ];
  }

  public function getDocumentPlugins() {
    return [
      'csv' => '\Drupal\forena\FrxPlugin\Document\CSV',
      'drupal' => '\Drupal\forena\FrxPlugin\Document\Drupal',
      'doc' => '\Drupal\forena\FrxPlugin\Document\Word',
      'email' => '\Drupal\forena\FrxPlugin\Document\EmailMerge',
      'html' => '\Drupal\forena\FrxPlugin\Document\HTML',
      'svg' => '\Drupal\forena\FrxPlugin\Document\SVG',
      'xls' => '\Drupal\forena\FrxPlugin\Document\Excel',
      'xml' => '\Drupal\forena\FrxPlugin\Document\XML',
    ];
  }
  
  public function getDriverPlugins() {
    return [
      'FrxDrupal' => '\Drupal\forena\FrxPlugin\Driver\FrxDrupal',
      'FrxFiles' => '\Drupal\forena\FrxPlugin\Driver\FrxFiles',
      'FrxMSSQL' => '\Drupal\forena\FrxPlugin\Driver\FrxMSSQl',
      'FrxOracle' => '\Drupal\forena\FrxPlugin\Driver\FrxOracle',
      'FrxPDO' => '\Drupal\forena\FrxPlugin\Driver\FrxPDO',
      'FrxPostgres' => '\Drupal\forena\FrxPlugin\Driver\FrxPostgres',
    ];
  }

  /**
   * Return the forena provided formatter plugin. 
   * @return array
   */
  public function getFormatterPlugins() {
    return ['\Drupal\forena\FrxPlugin\FieldFormatter\Formatter'];
  }

  public function buildParametersForm($parameters) {
    $controller = $this->parameterForm;
    $form = [];
    $form = $controller->buildForm($form, $this->form_state, $parameters);
    return $form;
  }


  public function currentPath() {
    return $this->currentPath;
  }

  public function error($short_message='', $log='') {
    echo "$short_message\n";
    echo "$log\n";
  }

  public function debug($short_message='', $log='') {
    echo "$short_message\n";
    echo "$log\n";
  }

  public function dataDirectory() {
    return $this->data_directory;
  }

  public function drupalRender(&$elements) {
    return $elements;
  }

  // Override ReportLink because of URL services.
  public function reportLink($text, $field) {
    $field['href'] = $field['link'];
    unset($field['link']);
    $field = array_filter($field);
    $attributes = '';
    foreach ($field as $key => $attr) {
      $attributes .= "$key='$attr' ";
    }
    $attributes = trim($attributes);
    return "<a $attributes>$text</a>";
  }

  public function url($path, $options) {
    if (strpos($path,'/')!== 0 && strpos($path, 'http' == FALSE)) {
      $path  = "/$path";
    }
    $url = $path; 
    if (isset($options['query'])) {
      $query_string = '?'; 
      foreach($options['query'] as $key => $value) {
        $query_string .= "$key=$value&"; 
      }
      $query_string = rtrim($query_string, '&'); 
      $url .= $query_string; 
    }
    return $url;
  }

  /**
   *
   * @param $menu_id
   * @param $max_depth
   * @return mixed
   */
  public function renderMenu($menu_id, $options=[]) {
    return "<ul class='$menu_id'><li>Menu Item</li></ul>\n";
  }


}