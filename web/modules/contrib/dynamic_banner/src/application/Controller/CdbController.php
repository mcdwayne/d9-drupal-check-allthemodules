<?php 
namespace Drupal\dynamic_banner\application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Query\TableSortExtender;
//use Drupal\Core\Entity;

class CdbController extends ControllerBase {
/**
 * Return a listing of all defined URL aliases.
 */
function adminListPage() {
  $output = ""; // default 

  // construct the headers of the table
  $header = array(
    array(
      'data'  => t('Url'),
      'field' => 'd.path',
      'sort'  => 'asc',
    ),
    array(
      'data'  => t('ImgUrl'),
    ),
    array(
      'data'  => t('Text'),
      'field' => 'd.text',
    ),
    array(
      'data'  => t('Link'),
      'field' => 'd.link',
    ),
    array(
      'data'  => t('Mode'),
      'field' => 'd.mode',
    ),
    array(
      'data'    => t('Operations'),
      'colspan' => '2',
    ),
  );

    $query = \Drupal::database()->select('dynamic_banner', 'd')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
      $query = $query->fields('d',  array('dbid', 'path', 'imgurl', 'imgfid', 'text', 'link', 'mode'));
      $query= $query->limit(10)->orderByHeader($header);
      $result = $query->execute();

  // start constructing the individual rows
  $rows = array();

  foreach ($result as $data) {
    $editUrl = Url::fromRoute('cdb.banneredit', array('bid' => $data->dbid ));
    $editLink  =\Drupal::l(t('Edit'), $editUrl); 

    $delUrl = Url::fromRoute('cdb.bannerdel', array('bid' => $data->dbid ));
    $delLink  =\Drupal::l(t('Delete'), $delUrl);

    $image = $this->dynamic_banner_image_handler($data->imgurl, $data->imgfid);    
    $rows[] = array('data' => 
      array(
        $data->path,
        $image,
        $data->text,
        $data->link,
        $data->mode,
        $editLink,
        $delLink
      )
    );
  }
  
  // construct the call for the theme function to run on this
  $output['dynamic_banner_table'] = array(
    '#theme'  => 'table', 
    '#header' => $header, 
    '#rows'   => $rows, 
    '#empty'  => t('No Banners Found.'),
  );
  
  // adds the pager buttons to the bottom of the table
  $output['dynamic_banner_pager'] = array('#type' => 'pager');

  // let drupal handle print and echo
  return $output;
}

/**
 * Fetch a specific banner from the database.
 */
function dynamic_banner_load_banner($dbid) {
  $query = db_select('dynamic_banner', 'd');
  $query->condition('d.dbid', $dbid, '=')
    ->fields('d');
  $result = $query->execute()->fetchObject();

  if ($result) {
    return $result;
  }
  return NULL;
}

/**
 * Find the default banner and return all of it's attributes
 */
function dynamic_banner_find_load_default() {
  $query = db_select('dynamic_banner', 'd');
  $query->condition('d.path', 'DEFAULT', '=')
    ->fields('d');
  $result = $query->execute()->fetchObject();

  if ($result) {
    return $result;
  }
  
  // do not return null for this
  $blank_banner = new stdClass();
  $blank_banner->dbid = 0;
  $blank_banner->path = 'DEFAULT';
  $blank_banner->imgurl = '';
  $blank_banner->mode = 'normal';
  $blank_banner->text = '';
  $blank_banner->link = '';
  $blank_banner->imgfid = '';
  return $blank_banner;
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
      $all_files = \Drupal\file\Entity\File::loadMultiple((int)$all_fids);
      $retval = "";// default the return string
      // go into all the loaded files
      if(isset($all_files)){
         foreach ($all_files as $file) {
            // if this is the first time through do not add a comma to the string
            if ($retval != "") {
              $retval .= ",";
            }
            // have to translate the public string in the uri back into something browsers understand
            if(isset($file)){ $fileUrl = $file->getFileUri(); } else { $fileUrl = ''; }
            $retval .= str_replace('public://', '/', $fileUrl);
        }
      }
      
      return $retval;
    }
    else {
      $file = \Drupal\file\Entity\File::load((int)$imgfid);
      if(isset($file)){
          $fileUrl = $file->getFileUri();
      }else{
          $fileUrl = '';
      }
     
      $file_path = str_replace('public://', '/', $fileUrl);
      return $file_path;
    }
  }
}



}