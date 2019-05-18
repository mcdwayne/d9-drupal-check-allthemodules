<?php
/**
 * @file FrxInclude
 * Include a reference to another report as an asset.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Renderer;
use Drupal\Core\Url;
use Drupal\forena\AppService;
use Drupal\forena\ReportManager;
use SimpleXMLElement;
/**
 * Include a reprot
 *
 * @FrxRenderer(id = "FrxInclude")
 */
class FrxInclude extends RendererBase {
  public function render() {
    // Get data from source
    $attributes = $this->mergedAttributes();
    $output = '';

    // Determine data type
    $include = @$attributes['src'];
    $title = @$attributes['title'];
    // Quit if we have no data.
    if (!$include) return '';

    // Reformat URL
    @list($url, $query_str)=@explode('?', $include);
    $url = $this->report->replace($url, TRUE);
    $report_url = $url;
    $parts = @explode('/', $url);
    $file = @$parts[count($parts) - 1];
    $parts = explode('.', $file);
    // Determine file extention
    $ext = count($parts) > 1 ? $parts[count($parts) - 1] : '';
    $query = array();
    if ($query_str) {
      parse_str($query_str, $query );
      foreach ($query as $key=>$value) {
        $query[$key] = $this->teng->replace($value, TRUE);
      }
    }

    // Build URL
    $options = array('query' => $query);
    $url = AppService::instance()->url($url, $options);
    //$url = url($url, $options);

    $mode = isset($attributes['mode']) ? $attributes['mode'] : '';

    switch ($mode) {
      case 'ajax':
        if(strpos($url,'/nojs/')=== FALSE) {
          if (!isset($attributes['id'])) $attributes['id'] = 'frx-include';
          $id = @$attributes['id'];
          $url .= "/nojs/$id/replace";
          if (isset($attributes['class'])) {
            $attributes['class'] .= ' use-ajax forena-autoload';
          }
          else {
            $attributes['class'] = 'use-ajax forena-autoload';
          }
        }
        $output = $this->render_reference($url, $ext, $attributes, $title);
        break;
      case 'reference':
        $output = $this->render_reference($url, $ext, $attributes, $title);
        break;
      case 'inline':
      default:
        ReportManager::instance()->reportInclude(str_replace('reports/', '', $report_url));
    }
    return $output;
  }

  function render_reference($url, $ext, $attributes, $title) {
    $ext = strtolower($ext);
    if (!$title) $title = "$ext document";
    $attributes = $this->teng->replace($attributes);
    switch ($ext) {
      case 'png':
      case 'gif':
      case 'jpg':
      case 'jpeg':
        $x = new SimpleXMLElement('<img/>');
        $x['src'] = $url;
        if (isset($attributes['height'])) $x['height'] = $attributes['height'];
        if (isset($attributes['width'])) $x['width'] = $attributes['width'];
        break;
      case 'svg':
        $x = new SimpleXMLElement('<embed/>');
        $x['src'] = $url;
        $x['type'] = 'image/svg+xml';
        $x['pluginspage'] = "http://www.adobe.com/svg/viewer/install/";
        if (isset($attributes['height'])) $x['height'] = $attributes['height'];
        if (isset($attributes['width'])) $x['width'] = $attributes['width'];
        break;
      default:
        $x = new SimpleXMLElement('<a>' . htmlentities($title, ENT_QUOTES) . '</a>' );
        $x['href'] = $url;
    }

    if (isset($attributes['id'])) $x['id'] = $attributes['id'];
    if (isset($attributes['class'])) $x['class'] = $attributes['class'];
    return $x->asXML();
  }


}