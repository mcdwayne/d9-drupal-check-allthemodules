<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/25/2016
 * Time: 11:16 AM
 */

namespace Drupal\forena\FrxPlugin\Renderer;

use Drupal\forena\FrxPlugin\Document\DocumentInterface;
use Drupal\forena\Report;
use DOMNode;

interface RendererInterface {
  public function __construct(Report $report, DocumentInterface $doc = NULL);

  public function initReportNode(DOMNode $domNode);

  public function render();
}