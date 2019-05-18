<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/25/16
 * Time: 6:29 AM
 */

namespace Drupal\Tests\forena\Unit\FrxPlugin\Context;


use Drupal\forena\FrxPlugin\Context\ContextBase;

class CustomSecurity extends ContextBase{

  public $secure = 'not';
  
}