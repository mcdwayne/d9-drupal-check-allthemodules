<?php
/**
 * @file
 *
 * Contains Drupal\wisski_pathbuilder\Exporter
 */
    
namespace Drupal\wisski_pathbuilder\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Utility\LinkGeneratorInterface;    
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity;
    
class Exporter extends ControllerBase {

  public function exportPb($pb) {
    
    // get the pb if we only have the id
    
    // this seems to be discontinued?
    /*
    if (!is_object($pb)) {
      $pb = WisskiPathbuilderEntity::load($pb);
    }
    
    $dependencies = [];
    foreach ($pb->getPbArray() $pid => $path_info) {
      $dependencies[] = $
    }
   */

  }


}
