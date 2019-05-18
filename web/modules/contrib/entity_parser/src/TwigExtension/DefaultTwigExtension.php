<?php

namespace Drupal\entity_parser\TwigExtension;

use Drupal\Core\Render\Renderer;
use Drupal\entity_parser\EntityParser;

/**
 * Class DefaultTwigExtension.
 */

class DefaultTwigExtension extends \Twig_Extension {

        
   /**
    * {@inheritdoc}
    */
    public function getTokenParsers() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getNodeVisitors() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getFilters() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getTests() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getFunctions() {
        return [
            'entity_parser_load' => new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'entity_parser_load_twig']),
            'node_parser' => new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'node_parser_twig']),
            'block_parser' => new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'block_parser_twig']),
            'user_parser' => new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'user_parser_twig']),
            'taxonomy_term_parser'=> new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'taxonomy_term_parser_twig']),
             'group_parser'=> new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'group_parser_twig']),
            'group_content_parser'=> new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'group_content_parser_twig']),
             'paragraph_parser'=> new \Twig_Function_Function(['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'paragraph_parser_twig'])


        ];
    }

    public static function paragraph_parser_twig($term,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->paragraph_parser($term,$fields,$option);
    }

    public static function taxonomy_term_parser_twig($term,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->taxonomy_term_parser($term,$fields,$option);
    }
    public static function group_parser_twig($entity,$fields = [],$option = []){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->group_parser($entity,$fields,$option);
    }
    public static function group_content_parser_twig($entity,$fields = [],$option = []){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->group_content_parser($entity,$fields,$option);
    }
    public static function block_parser_twig($block,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
        $parser = new $option['#entity_parser_extend']();
        }
        return $parser->block_parser($block['#block_content'],$fields,$option);
    }
    public static function node_parser_twig($node,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->node_parser($node,$fields,$option);
    }
  public static function user_parser_twig($user,$fields = [],$option = [] ){
    $parser = new EntityParser();
    if(isset($option['#entity_parser_extend'])){
      $parser = new $option['#entity_parser_extend']();
    }
    return $parser->user_parser($user,$fields,$option);
  }
    public static function entity_parser_load_twig($entity,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->entity_parser_load($entity,$fields,$option);
    }

   /**
    * {@inheritdoc}
    */
    public function getOperators() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getName() {
      return 'entity_parser.twig.extension';
    }

}
