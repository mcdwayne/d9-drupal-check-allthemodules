<?php
/**
 * @file
 * Contains \Drupal\simplepodcast\Routing\PodcstRssCommonFunctions.
 */
 namespace Drupal\simplepodcast;

 use Drupal\Core\Site\Settings;
 use Drupal\image\Entity\ImageStyle;

 class SimplePodcastCommonFunctions {

   public static function getConfig($config_item) {
     $return_item = \Drupal::config('simplepodcast.settings')->get($config_item);

     if ( trim($return_item) == '' ) {
       switch($config_item) {
         case 'title':
           $return_item = \Drupal::config('system.site')->get('name');
           break;
         case 'description':
           $return_item = \Drupal::config('system.site')->get('slogan');
           break;
         case 'language':
           $return_item = \Drupal::languageManager()->getCurrentLanguage()->getId();
           break;
         case 'copyright':
           $site_name = \Drupal::config('system.site')->get('name');
           $return_item = date("Y").' '.$site_name;
           break;
         case 'owner_name':
           $return_item = \Drupal::config('system.site')->get('name');
           break;
           case 'owner_author':
             $return_item = \Drupal::config('system.site')->get('name');
             break;
         case 'owner_email':
           $return_item = \Drupal::config('system.site')->get('mail');
           break;
         case 'channel_image':
            $return_item = '';
            break;
         case 'item_title':
            $return_item = 'title';
            break;
          case 'item_author':
            $return_item = 'uid';
            break;
          case 'item_subtitle':
            $return_item = 'title';
            break;
          case 'item_summary':
            $return_item = 'body';
            break;
       }
     } elseif ( $return_item == 'owner@example.com' && $config_item == 'owner_email') {
       $return_item = \Drupal::config('system.site')->get('mail');
     }
     return $return_item;
   }
   public function getFieldValue($node,$field_name) {
      switch($field_name) {
        case 'uid':
          $field_value = $node->getOwner()->getAccountName();
          break;
        default:
          $field_value = $node->get($field_name)->value;
      }
     return $field_value;
   }
   public function getFieldImage($node,$field_name) {
     if ($node->get($field_name)->entity->uri->value) {
       return ImageStyle::load('large')->buildUrl($node->get($field_name)->entity->uri->value);
     } else return NULL;
   }
   
   public function getFieldUri($node,$field_name) {
     if ($node->get($field_name)->uri) {
       return $node->get($field_name)->uri;
     } else return NULL;
   }
 }