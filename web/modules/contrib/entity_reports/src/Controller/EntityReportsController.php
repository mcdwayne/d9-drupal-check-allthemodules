<?php

namespace Drupal\entity_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_reports\ReportGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EntityReportsController handle routes for custom entity reports.
 */
class EntityReportsController extends ControllerBase {


  /** @var \Drupal\entity_reports\ReportGenerator */
  protected $generator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_reports.generator')
    );
  }

  public function __construct(ReportGenerator $reportGenerator) {
    $this->generator = $reportGenerator;
  }

  /**
   * @param string $type
   *  The content type of response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  The response object.
   */
  public function exportContentTypes($type = 'json') {
    $structure = $this->generator->generateContentTypesReport();
    return $this->export(['contentTypes' => $structure], $type);
  }

  /**
   * @param string $type
   *  The content type of response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  The response object.
   */
  public function exportTaxonomyStructure($type = 'json') {
    $structure = $this->generator->generateTaxonomyReport();
    return $this->export(['vocabularies' => $structure], $type);
  }

  /**
   * @param string $type
   *  The paragraph type of response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  The response object.
   */
  public function exportParagraphTypes($type = 'json') {
    $structure = $this->generator->generateParagraphTypesReport();
    return $this->export(['paragraphTypes' => $structure], $type);
  }

  /**
   * Provides the content in a specified format.
   *
   * @param $content
   *  The content of response.
   * @param string $type
   *  The content type of response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  The response object.
   */
  public function export(array $content, $type = 'json') {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/' . $type);
    switch ($type) {
      case 'xml':
        if (count($content) == 1) {
          $rootElement = key($content);
          $content = reset($content);
        }
        else {
          $rootElement = 'root';
        }
        $xml = new \SimpleXMLElement("<{$rootElement}/>");
        $this->arrayToXml($content, $xml, $rootElement);
        $content = $xml->asXML();
        break;

      default:
        $content = json_encode($content);
    }
    $response->setContent($content);
    return $response;
  }


  protected function arrayToXml($array, \SimpleXMLElement &$xml, $parentKey) {
    foreach ($array as $key => $value) {
      $xmlKey = !is_numeric($key) ? $key : "item$key";
      $attributes = [];
      switch ($parentKey) {
        case 'contentTypes':
          $xmlKey = 'contentType';
          $attributes['id'] = $key;
          break;

        case 'vocabularies':
          $xmlKey = 'vocabulary';
          $attributes['id'] = $key;
          break;

        case 'fields':
          $xmlKey = 'field';
          $attributes['id'] = $key;
          break;

        case 'terms':
          $xmlKey = 'term';
          $attributes['id'] = $key;
          break;
      }
      if (is_array($value)) {
        $subnode = $xml->addChild($xmlKey);
        $this->arrayToXml($value, $subnode, $xmlKey);
      }
      else {
        $subnode = $xml->addChild($xmlKey, $value);
      }
      foreach ($attributes as $attrKey => $attrValue) {
        $subnode->addAttribute($attrKey, $attrValue);
      }
    }
  }


}
