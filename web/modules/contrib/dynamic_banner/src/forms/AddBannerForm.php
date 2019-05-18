<?php
namespace Drupal\dynamic_banner\forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
//dynamic_banneruse Drupal\formbuilder\forms\FormBuilderModel; 

class AddBannerForm extends FormBase {

  public function getFormID() {
    return 'frm_addbannerform';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
  
  $current_path = \Drupal::service('path.current')->getPath(); // Gets internal path - for eg /node/29.
  $pathArgs = explode('/', $current_path);
  
  $dbid = (isset($pathArgs[5])) ? $pathArgs[5]: ''; // the last portion of the url there must be a better way of doing this
  
  // This is used by the file handler, It is needed to accept files
  $form['#attributes'] = array('enctype' => "multipart/form-data");
  
  $file_path = drupal_get_path('module', 'file');
  
  // default the variables here
  $banner = NULL; // prevent bugs nulify the variable
  $default_flag = FALSE; // enable variable in this scope
  
  // hide it so that the user may not change this element
  $form['dbid'] = array(
    '#type'          => 'hidden',
    '#required'      => FALSE,
  );

  if ($dbid == '' || $dbid == '0') {
    // this will disable the path field for the default banner
    if (isset($_GET['q']) && strrpos($_GET['q'], "/default")) {
      $form['#title'] = $this->t('Default Banner');
      // load the default if there is one
      $banner = $this->dynamic_banner_find_load_default();
    }
    else {
      $form['#title'] = $this->t('New Banner');
      $form['dbid']['#default_value'] = NULL;
    }
  }
  else {
    // The dbid is set so a banner must exist load it
    $banner = $this->dynamic_banner_load_banner($dbid);
    $form['#title'] = $this->t('Edit Banner');
    //print_r($banner);
   // exit;
    //drupal_set_title(t("Edit Banner") . " '" . $banner->path . "'");
    $form['dbid']['#default_value'] = $dbid;
  }
  
  // this will prevent the used from changing this field once the default has been loaded
  // it deals with a bug if the person chose to edit the specific banner for default rather than pressing default
  if ($banner && $banner->path == 'DEFAULT') {
    $default_flag = TRUE;
  }
  
  // disable the path form element when the default flag is out
  if (!$default_flag) {
    $form['path'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Banner Path'),
      '#default_value' => $banner ? $banner->path : "",
      '#size'          => 45,
      '#maxlength'     => 250,
      '#description'   => t('Specify an existing url path you wish to put a banner on. For example: home, user* (wild card), content! (random). Enter a path as it appears in the url of your site.'),
      '#field_prefix'  => Url::fromRoute('<front>', [], ['absolute' => TRUE]),
      '#required'      => TRUE,
    );
  }
  else {
    $form['path'] = array(
      '#type'          => 'hidden',
      '#title'         => t('Banner Path'),
      '#default_value' => 'DEFAULT',
    );
  }
  
  /*// if the module exists add the autocomplete path
  // i might have to do my own autocomplete here cause mpac doesnt really do what i need it to do
  if ( module_exists('mpac') ) {
    $form['path']['#autocomplete_path'] = 'mpac/autocomplete/alias';
  }*/
  
  $img_arr = array(t('Use Existing Image(s)'), t('Upload New Image(s)'));
  $form['image_type'] = array(
    '#type'    => 'radios',
    '#options' => array_combine($img_arr, $img_arr),
    '#title'   => t('Choose image type.'),
    //'#required' => TRUE,
  );
  
  if ( $banner && isset($banner->imgurl)) {
    $form['image_type']['#default_value'] = t('Use Existing Image(s)');
  }elseif ( $banner && isset($banner->imgfid)) {
    $form['image_type']['#default_value'] = t('Upload New Image(s)');
  }

  /**
   * Note: There are two form elements for the same thing 
   * They are both not required but only one is needed for proper handling
   * When we are loading an old banner load the url into imgurl
   * When we are uploading a new image the validator will upload the image store it and fill in imgurl for you
   * Only use one method no mix and matching
   * When reading the data use checks to see which method was used
   */
  global $base_url;
  $form['imgurl'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Typeout the url of the image'),
    '#default_value' => $banner ? $banner->imgurl : '',
    '#description'   => t('Specify an image(s) for the banner to display.'),
    '#field_prefix'  => $base_url.'/sites/default/files',
    '#states' => array(
      'visible' => array(
        ':input[name="image_type"]' => array('value' => 'Use Existing Image(s)'),
      ),
    ),
    //'#required'    => TRUE,
  );
 
  $form['image'] = array(
    '#title'              => t('Choose Image File'),
    '#type'               => 'managed_file',
    '#default_value'      => $banner ? array($banner->imgfid) :0,
    '#progress_indicator' => 'throbber',
    '#progress_message'   => NULL,
    '#upload_location'    => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_file_save_path', BANNER_DEFAULT_SAVE_LOCATION ),     
    '#description'        => t('Specify an image(s) for the banner to display.'),
    '#upload_validators' => [
        'file_validate_extensions' => array('jpg jpeg png gif'),
    ],
    '#states' => array(
      'visible' => array(
        ':input[name="image_type"]' => array('value' => 'Upload New Image(s)'),
      ),
    ),
  );

  /** 
   * Since upon pressing the delete button on the image the fid is set to 0
   * We need to save is because we still need to delete that image.
   */
  $form['oldimagefid'] = array(
    '#type'          => 'hidden',
    '#required'      => FALSE,
    '#default_value' => $banner ? $banner->imgfid : 0,
  );
  
  $form['text'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Text'),
    '#default_value' => $banner ? $banner->text : '',
    '#maxlength'     => 250,
    '#size'          => 45,
    '#description'   => t('Specify the text to associate with this banner [comma seperated for randoms, also must match amount of elements from images] (optional).'),
    '#required'      => FALSE,
  );

  $form['link'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Link'),
    '#default_value' => $banner ? $banner->link : '',
    '#maxlength'     => 250,
    '#size'          => 45,
    '#description'   => t('Specify the link you want your banner to point to (optional).'),
    '#required'      => FALSE,
  );

  $mode_arr = array(t('normal'), t('time_based'), t('rotating'), t('fade'));
  $form['mode'] = array(
    '#type'          => 'radios',
    '#title'         => t('Mode'),
    '#options'       => array_combine($mode_arr, $mode_arr),
    '#default_value' => $banner ? $banner->mode : BANNER_DEFAULT_BANNER_MODE,
    '#description'   => t('What mode do you want this banner to display under (this is different than display setting)'),
    '#required'      => TRUE,
  );
  /*
  $form['time_on'] = array(
    '#type'          => 'date',
    '#title'         => t('Start Time'),
    '#description'   => t('Specify the time you want your banner to start displaying (optional).'),
    '#required'      => FALSE,
    '#states'        => array(
      'visible'      => array(
        ':input[name="mode"]' => array('value' => t('time_based')),
      ),
    ),
  );
  
  $form['time_off'] = array(
    '#type'          => 'date',
    '#title'         => t('End Time'),
    '#description'   => t('Specify the time you want your banner to stop displaying (optional).'),
    '#required'      => FALSE,
    '#states'        => array(
      'visible'      => array(
        ':input[name="mode"]' => array('value' => t('time_based')),
      ),
    ),
  );*/

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Banner'),
  );
  $form['#attached']['library'][] = 'dynamic_banner/bannerattach';
  return $form;

 }



  public function validateForm(array &$form, FormStateInterface $form_state) {

   //No need to validate  
    // For a banner to exist it needs a path that it is assigned to and an image, thats it.


  if (($form_state->getValue('path') != '')  &&
      (($form_state->getValue('image') != '') || ($form_state->getValue('imgurl') != ''))) {

    $path = $form_state->getValue('path');
    $dbid = $form_state->getValue('dbid');

    if ($path != 'DEFAULT' && $dbid == '') {
      // check db before altering the path variable
      // check for more than one of the same path banners
      $query = \Drupal::database()->select('dynamic_banner', 'frm')
                  ->fields('frm', array('path'))
                  ->condition('path', $path)
                  ->execute();
      $result = $query->fetchAll();
     
      if (isset($result[0]) && $result[0]->path) {
        $form_state->setErrorByName('path', t('The path %path is already in use.', array('%path' => $path)));
          return;
      }
      // path is not clean at this point because of wildcard and random must chop those characters off
      // find the * or wildcard
      $wild_position = strrpos($path, "*");
      if ($wild_position !== FALSE) {
        $path = drupal_substr($path, 0, $wild_position);
      }
      // find the ! or random
      $rand_position = strrpos($path, "!");
      if ($rand_position !== FALSE) {
        $path = drupal_substr($path, 0, $rand_position);
      }

      // D8
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/'.$path);

      if (strcmp($alias, $path) == 0) {
          $alias = '';  // No alias was found.
         $form_state->setErrorByName('path', t('The path %path is not known by drupal.', array('%path' => $path)));
       
        return;
      }else{
        if ( $dbid != 0) {
          return;
        }
      }
 
     }

    /*
     * Image validation & upload
     */
    $image = $form_state->getValue('image');
    if ( isset($image[0]) && $image[0] != '' && $image[0] != 0) {
      //$file = file_load($form_state->getValue('image'));
       
       $file = \Drupal\file\Entity\File::load((int)$image[0] );
        if ($file) {
          $image = $form_state->getValue('image');  
          $file->setPermanent();
          $file_usage = \Drupal::service('file.usage');
          $file_usage->add($file, 'dynamic_banner', 'banner', 1);
          // Save the file again for permanent status
           $file->save();
        }
        else {
          $form_state->setErrorByName('image', t('Failed to write the uploaded file to the folder.'));
          return;
        }
      }
      // Delete the image
      elseif ($form_state->getValue('image') == 0) {

        echo "inside image is 0";
        $this->dynamic_banner_image_delete($form_state->getValue('oldimagefid'));
      }
     /*
      * Image validation & upload ends here 
      */
  }
  else {
    $form_state->setErrorByName('path', t('There was a problem with the required fields please check the form and try again.'));
    return;
  }

  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
     
   //define a sort of struct array for display mode for form translation
  //$mode_struct = array('normal', 'time_based', 'rotating', 'fade');
  
  // extra validation check to make sure
  if ($form_state->getValue('image_type') == t('Use Existing Image(s)')) {
    $imgurl = $form_state->getValue('imgurl');
  }
  else {
    $imgurl = NULL;
  }
  if ($form_state->getValue('image_type') == t('Upload New Image(s)')) {
    $imgfid = $form_state->getValue('image');
  }
  else {
    $imgfid = NULL;
  }
  
  $path     = $form_state->getValue('path');
  $text     = $form_state->getValue('text');
  $link     = $form_state->getValue('link');
  $mode     = $form_state->getValue('mode');
  $dbid     = $form_state->getValue('dbid');
  
  $time_on  = NULL;
  $time_off = NULL;

   // Save the banner
  $this->dynamic_banner_set_banner($path, $imgurl, $imgfid, $text, $link, $mode, $time_on, $time_off, $dbid);

  drupal_set_message(t('The banner has been saved.'));
  $form_state->setRedirect('cdb.listbanners'); 
  return;
  } 

 /**
  * Set a banner for a given path, preventing duplicates.
  * Note if dbid comes in null then we are creating a banner
  */
  public function dynamic_banner_set_banner($path, $imgurl, $imgfid, $text, $link, $mode, $time_on, $time_off, $dbid = NULL) {
      if (!$mode) {
        $mode = BANNER_DEFAULT_BANNER_MODE;
      }

      // First we check if we are dealing with an existing alias and delete or modify it based on dbid.
      // we dont need to do a complicated check here because the code already made it for us
      if ($dbid) {
        // Update the existing banner.
        db_update('dynamic_banner')->fields(array(
          'path'       => $path,
          'imgurl'     => $imgurl,
          'imgfid'     => (isset($imgfid[0]))? $imgfid[0] : '',
          'text'       => $text,
          'link'       => $link,
          'mode'       => $mode,
          'start_time' => $time_on,
          'end_time'   => $time_off,
        ))->condition('dbid', $dbid)->execute();
      }
      else {
        db_insert('dynamic_banner')->fields(array(
          'path'       => $path,
          'imgurl'     => $imgurl,
          'imgfid'     => (isset($imgfid[0]))? $imgfid[0] : '',
          'text'       => $text,
          'link'       => $link,
          'mode'       => $mode,
          'start_time' => $time_on,
          'end_time'   => $time_off,
        ))->execute();
      }
}

/**
 * Post-confirmation; delete a Banner
 */
function dynamic_banner_admin_delete($dbid = 0) {
  db_delete('dynamic_banner')->condition('dbid', $dbid)->execute();
  drupal_set_message(t('The banner has been deleted, the image still exists though'));
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
} */

/**
 * This function will split the csv fid variable if it needs to be split
 * And then delete those images from the file system and thier values in the db
 */
public function dynamic_banner_image_delete($fid) {
  

    if ( strrpos($fid, ',') ) {
    // split the plain string into an array
    $all_fids = explode(",", $fid);
    // load all files at once
    $all_files = file_load_multiple($all_fids);
    
    if(is_array($all_files) && !empry($all_files)) {
      foreach($all_files as $file) {
        if ($file) {
          // When a module is managing a file, it must manage the usage count.
          // Here we decrement the usage count with file_usage_delete().
          file_usage_delete($file, 'dynamic_banner', 'banner', 1);

          // The file_delete() function takes a file object and checks to see if
          // the file is being used by any other modules. If it is the delete
          // operation is cancelled, otherwise the file is deleted.
          file_delete($file);
        }
      
        drupal_set_message(t('The image @image_name was removed.', array('@image_name' => $file->filename)));
      }
    }
  }
  else {
    $file = $fid ? file_load($fid) : FALSE;
    
    if ($file) {
      // When a module is managing a file, it must manage the usage count.
      // Here we decrement the usage count with file_usage_delete().
      file_usage_delete($file, 'dynamic_banner', 'banner', 1);

      // The file_delete() function takes a file object and checks to see if
      // the file is being used by any other modules. If it is the delete
      // operation is cancelled, otherwise the file is deleted.
      file_delete($file);
    }
    if(isset($file->filename)){$fileName = $file->filename; } else { $fileName = '';}
    drupal_set_message(t('The image @image_name was removed.', array('@image_name' => $fileName)));
  }
}

}