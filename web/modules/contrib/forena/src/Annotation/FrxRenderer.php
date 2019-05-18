<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/25/2016
 * Time: 9:10 AM
 */

namespace Drupal\forena\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Defines an FrxDocument item annotation object
 *
 * @see \Drupal\forena\DocumentPluginManager
 *
 * @Annotation
 */
class FrxRenderer extends Plugin {
  /**
   * FrxAPI Document Plugin id
   *
   * @var string
   */
  public $id;

}