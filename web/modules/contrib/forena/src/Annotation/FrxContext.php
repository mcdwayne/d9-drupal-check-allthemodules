<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/26/16
 * Time: 7:01 AM
 */

namespace Drupal\forena\Annotation;
use Drupal\Component\Annotation\Plugin;

/**
 * FrxContextPlugin annotation.
 *
 * @see \Drupal\forena\ContextPluginManager
 *
 * @Annotation
 */
class FrxContext extends Plugin{
  // ID or internal name of the command.
  public $id;
}