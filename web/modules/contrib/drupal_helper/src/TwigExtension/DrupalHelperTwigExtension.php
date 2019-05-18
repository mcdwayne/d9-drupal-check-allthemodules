<?php

namespace Drupal\drupal_helper\TwigExtension;
use Drupal\drupal_helper\DrupalHepler;


/**
 * Class DrupalHelperTwigExtension.
 */
class DrupalHelperTwigExtension extends \Twig_Extension {

        
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
          'current_user' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_user']),
          'current_lang' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_lang']),
          'is_login' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'isLoginTwig']),
          'current_url' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_url']),
          'get_parameter' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_get_parameter']),
          'node_url' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'node_url']),
          'block_load_by_type' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'block_load_by_type']),
          'get_module_path' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_module_path']),
          'base_url' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'base_url']),
          'taxonomy_url' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'taxonomy_url']),
          'die' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'die_twig']),
          'array_values' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'array_value_twig']),
          'hasRole' => new \Twig_Function_Function(['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'has_role_twig'])
      ];
    }
    public static function has_role_twig($role_name,$uid){
      $twig_base = new DrupalHepler();
       return $twig_base->user->hasRole($role_name,$uid);
    }
    public static function array_value_twig($array){
      return  array_values($array);
    }
    public static function twig_current_lang(){
        return  $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    public static function twig_current_user(){
        $twig_base = new DrupalHepler();
        return  $twig_base->helper->current_user();
    }
    public  static function die_twig(){
        return die();
    }
    public static function isLoginTwig() {
        $twig_base = new DrupalHepler();
        return  $twig_base->helper->is_login();
    }
    public static function twig_current_url(){
        $twig_base = new DrupalHepler();
        return  $twig_base->helper->current_url();
    }
    public static function twig_get_parameter(){
        return   \Drupal::request()->query->all();
    }


    public static function node_url($nid){
        $twig_base = new DrupalHepler();
        return $twig_base->helper->node_url($nid);
    }
    public static function block_load_by_type($type){
        $twig_base = new DrupalHepler();
        return $twig_base->helper->block_custom_load_by_type($type);

    }

    public static function  get_module_path($module_name){
        $twig_base = new DrupalHepler();
        return $twig_base->helper->get_module_path($module_name);
    }

    public  static function base_url(){
        global $base_url ;
        return $base_url;
    }

    public  static function taxonomy_url($tid){
        $twig_base = new DrupalHepler();
        return $twig_base->helper->taxonomy_url_alias($tid);
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
      return 'drupal_helper.twig.extension';
    }

}
