<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/28/16
 * Time: 6:50 PM
 */

namespace Drupal\forena\Template;


interface TemplateInterface {
  public function configure($config);
  public function scrapeConfig(\SimpleXMLElement $xml);
  public function generate();
}