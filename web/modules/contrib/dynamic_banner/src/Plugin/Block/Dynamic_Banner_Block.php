<?php
namespace Drupal\dynamic_banner\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\file\Entity\File;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "Dynamic Banner",
 *   admin_label = @Translation("Dynamic Banner"),
 *   category = @Translation("Dynamic Banner"),
 * )
 */
class Dynamic_Banner_Block extends BlockBase {
  public function build() {

  // store the path of the page the block is loading from, this will sead our first searches
      //$path = drupal_strtolower(drupal_get_path_alias($_GET['q']));
      $current_path = \Drupal::service('path.current')->getPath();
      $path = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
      $path  = trim($path,'/');
       do {
            $result = NULL;
            $query = db_select('dynamic_banner', 'd');
            $query->condition('d.path', $path, '=')->fields('d');
            $result = $query->execute()->fetchObject();

            // search for that path string exact match
            if ($result) {
              // have to translate if we have fids
              // image should always be in path format (sites/default/banners/pic0.jpg,sites/default/banners/pic1.jpg)
              if(!empty($result->imgurl) || !empty($result->imgfid)) {
                 $image = $this->dynamic_banner_image_handler($result->imgurl, $result->imgfid);
              }else{
                $image = ""; 
              }
              $bannerarr = array(
                  'url'             => $image,
                  'text'            => $result->text,
                  'link'            => $result->link,
                  'display_setting' => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_setting', BANNER_DEFAULT_OUTPUT),
                  'display_errors'  => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_errors', BANNER_DEFAULT_ERROR),
                  );
             // return array('content' => theme('banner_output', $bannerarr));
              return [
                  '#theme' => 'banner_output',
                  '#bannerarr' => $bannerarr,
               ];
            }

            // wild section //
            $result = NULL;
            $wild_search = $path . '*';
            
            // create and execute query
            $query = db_select('dynamic_banner', 'd');
            $query->condition('d.path', $wild_search, '=')
              ->fields('d');
            $result = $query->execute()->fetchObject();

            // search for the wild card string exact match
            if ($result) {
              // have to translate if we have fids
              // image should always be in path format (sites/default/banners/pic0.jpg,sites/default/banners/pic1.jpg)

              if(!empty($result->imgurl) || !empty($result->imgfid)) {
                 $image = $this->dynamic_banner_image_handler($result->imgurl, $result->imgfid);
              }else{
                $image = ""; 
              }
              $bannerarr = array(
                'url'             => $image,
                'text'            => $result->text,
                'link'            => $result->link,
                'display_setting' => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_setting', BANNER_DEFAULT_OUTPUT),
                'display_errors'  => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_errors', BANNER_DEFAULT_ERROR),
               );  
             // return array('content' => theme('banner_output', $bannerarr));
               return [
                  '#theme' => 'banner_output',
                  '#bannerarr' => $bannerarr,
               ];
            }

            // random section //
            $result = NULL;
            $random_search = $path . '!';
            
            // create and execute query
            $query = db_select('dynamic_banner', 'd');
            $query->condition('d.path', $random_search, '=')
              ->fields('d');
            $result = $query->execute()->fetchObject();

            // search for that random string exact match
            if ($result) {
              // get extra stuff associated with randoms
              if(!empty($result->imgurl) || !empty($result->imgfid)) {
                 $images = $this->dynamic_banner_image_handler($result->imgurl, $result->imgfid);
              }else{
                $images = ""; 
              }
              // support for random text if needed
              $texts = $result->text;
              // explode comma seperated images and text
              $image = explode(",", $images);
              // support for random text if needed
              $text = explode(",", $texts);

              // count how many there are
              $count = count($image);

              // handle the random with ints (deal with array start at 0 problems)
              // so if there are 3 elements in the array it is 0-2 not 1-3 so generate random based on that
              $random = ($count - rand(0, $count - 1)) - 1;

              // remember text is optional
              $bannerarr = array(
                'url'             => $image[$random],
                'text'            => $text[$random],
                'link'            => $result->link,
                'display_setting' => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_setting', BANNER_DEFAULT_OUTPUT),
                'display_errors'  => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_errors', BANNER_DEFAULT_ERROR),
              );
             // return array('content' => theme('banner_output', $bannerarr));
              return [
                  '#theme' => 'banner_output',
                  '#bannerarr' => $bannerarr,
               ];
            }

            // chop off more of the string and try again, it is key to not modify the path before this point
            $last_slash_position = strrpos($path, "/"); // returns false if not found
            if ($last_slash_position !== FALSE) {
              //$path = drupal_substr($path, 0, $last_slash_position);  drupal_substr is undefineed
              $path = Unicode::substr($path, 0, $last_slash_position); 
            }
            else {
              $path = FALSE;
            }
        } while ($path != FALSE);
        // loop until we find the top down hirarchy

      // well no banner was found for this specific page if we have a default banner then display it
      ///////////////////////////// this will soon be stored in the variables table ////////////////// TODO
      
      // create and execute query
      $query = db_select('dynamic_banner', 'd');
      $query->condition('d.path', 'DEFAULT', '=')
        ->fields('d');
      $result = $query->execute()->fetchObject();

      // for the resultant row (SHOULD ALWAYS BE ONE)
      if ($result) {
        if(!empty($result->imgurl) && !empty($result->imgfid)) {
           $image = $this->dynamic_banner_image_handler($result->imgurl, $result->imgfid);
        }else{
          $image = ""; 
        }
        $bannerarr = array(
          'url'             => $image,
          'text'            => $result->text,
          'link'            => $result->link,
          'display_setting' => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_setting', BANNER_DEFAULT_OUTPUT),
          'display_errors'  => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_errors', BANNER_DEFAULT_ERROR),
         );
        //return array('content' => theme('banner_output', $bannerarr));
        return [
             '#theme' => 'banner_output',
             '#bannerarr' => $bannerarr,
        ];
      }
    // just encase something went wrong
     // return array('#markup' => 'Hello World');
  }

 /**
 * This function will load imgurl if there is no url for img
 * then it will load the fids into path format
 *
 * Input 1: The imgurl(s) that we are loading [maybe csv]
 * Input 2: The imgfid(s) that we are loading [maybe csv]
 */
 public function dynamic_banner_image_handler($imgurl, $imgfid) {
    // we have found the imgurl already in the right format return it
    if ($imgurl && $imgurl != '') {
      return $imgurl;
    }
    else {
      if ( strrpos($imgfid, ',') ) {
      // split the plain string into an array
      $all_fids = explode(",", $imgfid);
      // load all files at once
      $all_files = file_load_multiple($all_fids);
      $retval = "";// default the return string
      // go into all the loaded files
      foreach ($all_files as $file) {
        // if this is the first time through do not add a comma to the string
        if ($retval != "") {
          $retval .= ",";
        }
        // have to translate the public string in the uri back into something browsers understand
        if(isset($file)){ 
           $fileUrl = file_create_url($file->getFileUri());
        } else { $fileUrl = ''; }
        //$retval .= str_replace('public://', file_public_path() . '/', $fileUrl);
        $retval .= str_replace('public://', '/', $fileUrl);
      }
      return $retval;
    }
    else {
      $file = file_load((int)$imgfid);
      // have to translate the public string in the uri back into something browsers understand
      if(isset($file)){ 
          $fileUrl = file_create_url($file->getFileUri());
      } else { $fileUrl = ''; }
      $file_path = str_replace('public://', '/', $fileUrl);
      return $file_path;
    }
   }
 }

}
