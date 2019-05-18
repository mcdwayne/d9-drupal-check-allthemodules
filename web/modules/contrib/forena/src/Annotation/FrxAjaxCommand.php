<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/16/16
 * Time: 3:00 PM
 */

namespace Drupal\forena\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * FrxAjaxPlugin annotation. 
 *
 * @see \Drupal\forena\DocumentPluginManager
 *
 * @Annotation
 */
class FrxAjaxCommand extends Plugin{
  // ID or internal name of the command.
  public $id; 
}