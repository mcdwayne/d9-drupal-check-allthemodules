<?php

/**
 * @file
 * Contains \Drupal\sociallogin\Controller\SocialLoginController.
 */
namespace Drupal\socialprofiledata\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Social Login module routes.
 */
class SocialProfileDataController
 extends ControllerBase {

  protected $connection;

  public function __construct() {
    $this->connection = \Drupal\Core\Database\Database::getConnection();
  }

/**
 * Show User Profile at Dialog box
 *
 * @param type $js JS enabled
 * @param type $user_id User ID
 * @return string Conatin user profile data in renderable html form
 */
function showProfile($user) {

 $config = \Drupal::config('socialprofiledata.settings');
    $profilefield_value = $config->get('profile_selection');
    $noProfileData = true;
    $tables = array(
        'loginradius_basic_profile_data',
        'loginradius_extended_location_data',
        'loginradius_extended_profile_data',
        'loginradius_linkedin_companies',
        'loginradius_facebook_likes',
        'loginradius_facebook_events',
        'loginradius_status',
        'loginradius_facebook_posts',
        'loginradius_twitter_mentions',
        'loginradius_groups',
        'loginradius_contacts',
        'loginradius_videos',
        'loginradius_likes',
        'loginradius_photos',
        'loginradius_albums',
    );
    $loginradius_tabs = $array = array();
    $user_profile = array();

    foreach ($tables as $tables_value) {
        $table_profile_key = str_replace('loginradius_', '', $tables_value);
        if (isset($user) || in_array($table_profile_key, $profilefield_value)) {
            // basic profile data
            if ($result = $this->loginRadiusCheckExtendedProfile($tables_value, $user)) {
                $noProfileData = false;
                $loginradius_tabs[str_replace('loginradius_','', $tables_value)] = ucwords(str_replace(array('loginradius_', '_'), array('', ' '), $tables_value));
                $user_profile[$table_profile_key] = $result;
            }
        }
    }
    $output['loginradius_tabs'] = $loginradius_tabs;

    $output['noProfileData'] = $noProfileData;
  //  $smarty->assign('loginradius_tabs', $loginradius_tabs);
   // $smarty->assign('noProfileData', $noProfileData);
 $array =  array();
    if (!$noProfileData) {
        if (isset($user_profile['basic_profile_data']) && count($user_profile['basic_profile_data']) > 0) {
            $data = array(
                'General' => 'loginradius_basic_profile_data',
                'Email' => 'loginradius_emails',
            );
            $array = array_merge($array, $this->loginradius_social_profile_data_show_data_in_same_tabs($data, $user, $user_profile['basic_profile_data']));
            
        }

        if (isset($user_profile['extended_profile_data']) && count($user_profile['extended_profile_data']) > 0) {

            $data = array('loginradius_extended_profile_data', 'loginradius_positions', 'loginradius_education',
                'loginradius_phone_numbers', 'loginradius_IMaccounts', 'loginradius_addresses', 'loginradius_sports', 'loginradius_inspirational_people', 'loginradius_skills', 'loginradius_current_status', 'loginradius_certifications', 'loginradius_courses', 'loginradius_volunteer', 'loginradius_recommendations_received',
                'loginradius_languages', 'loginradius_patents', 'loginradius_favorites', 'loginradius_books', 'loginradius_games', 'loginradius_television_show', 'loginradius_movies',
            );

            $array = array_merge($array, $this->loginradius_social_profile_data_show_data_in_same_tabs($data, $user, $user_profile['extended_profile_data']));
        }
        if (isset($user_profile)) {
            foreach ($tables as $table_name) {
                if ($table_name == 'loginradius_extended_profile_data' || $table_name == 'loginradius_basic_profile_data') {
                    continue;
                }

                $profile_key = str_replace('loginradius_', '', $table_name);
                if (isset($user_profile[$profile_key]) && is_array($user_profile[$profile_key])) {
                    $array[$table_name] = $user_profile[$profile_key];
                }
            }
        }
    }
    $values =  array();
foreach ($array as $key => $value) {
  # code...
  $key_lr = $key;
  if(in_array($key,  array('loginradius_basic_profile_data', 'loginradius_emails'))){
    $key_lr = 'loginradius_basic_profile_data';
  }
  elseif(in_array($key,  array('loginradius_extended_profile_data', 'loginradius_positions', 'loginradius_education',
                'loginradius_phone_numbers', 'loginradius_IMaccounts', 'loginradius_addresses', 'loginradius_sports', 'loginradius_inspirational_people', 'loginradius_skills', 'loginradius_current_status', 'loginradius_certifications', 'loginradius_courses', 'loginradius_volunteer', 'loginradius_recommendations_received',
                'loginradius_languages', 'loginradius_patents', 'loginradius_favorites', 'loginradius_books', 'loginradius_games', 'loginradius_television_show', 'loginradius_movies',
            ))) {
  $key_lr = 'loginradius_extended_profile_data';
  }
  $key_lr = str_replace('loginradius_','', $key_lr);
   $values[$key_lr][$key] = $value;   
 if($key == 'loginradius_positions'){
   foreach($value as $k=>$val){
     foreach($val as $i=>$v){
     if($i == 'company'){
      $values[$key_lr][$key][$k]->$i = json_decode($v, true);
     }
     }
   }
}
}

  //  $smarty->assign('loginradius_tabs_data', array_filter($array));
$output['loginradius_tabs_data'] = array_filter($values);

$output = json_decode(json_encode($output), true);

return [
      '#theme' => 'show_profile',
      '#output' => $output,      
    ];
}

/**
 * Show extended data for specific user to admin
 *
 * @param type $tab_number Where to show user profile data in admin page
 * @param string $table_name Database table name
 * @param object $user_profile User profile data
 * @return string  Rendered html form to show user profile data at admin page
 */
function socialprofiledata_get_extended_data_to_show($tab_number, $table_name, $user_profile) {
  $output = '';

  if ($table_name == 'loginradius_basic_profile_data' || $table_name == 'loginradius_extended_profile_data') {
    return;
  }
  elseif (isset($user_profile[$table_name]) && count($user_profile[$table_name]) > 0) {
    $output .= '<div id="tabs-' . $tab_number . '">';
    $output .= $this->socialprofiledata_show($user_profile[$table_name]);
    $output .= '</div>';
  }

  return $output;
}
/**
 * Show data in same tabs in basic profile data and extended profile data.
 *
 * @param array $data Contain table name and heading value of tab
 * @param int $user_id User Id
 * @param array $profile_data Contain profile data
 * @param boolean $show True, if you want to show data in same tab
 * @return string Rendered html form
 */

function loginradius_social_profile_data_show_data_in_same_tabs($data, $userId, $profile_data)
{
   //$output = '';
$tab_data = array();
  foreach ($data as $key => $value) {

    if ($value == 'loginradius_basic_profile_data' || $value == 'loginradius_extended_profile_data') { 
      $result = $profile_data;
    }
    else {
      if ($this->connection->schema()->tableExists($value)) {
      $data_result = $this->connection->query("SELECT * FROM {" . $value . "} WHERE user_id = :uid", array(':uid' => $userId));
      $result = $data_result->fetchAll();
    }
    }
    if (isset($result) && count($result) > 0) {  
         $tab_data[$value] = $result;
    }
  }

  return $tab_data;
}

/**
 * Check table exist and get extended profile data.
 *
 * @param $table_name
 * @param $userId
 * @param $table_key
 * @return array|bool
 */
function loginRadiusCheckExtendedProfile($table_name, $userId)
{

  if ($this->connection->schema()->tableExists($table_name)) {
    $basic_result = $this->connection->query("SELECT * FROM {" . $table_name . "} WHERE user_id = :uid", array(':uid' => $userId));
  
    $basic_user_profile_data = $basic_result->fetchAll();

    if (isset($basic_user_profile_data) && count($basic_user_profile_data) > 0) {
       return $basic_user_profile_data;
    }
  }
}
/**
 * Show data in Sub table.
 *
 * @param array $array Collection of userprofile data
 * @param boolean $sub_table true , if show inside table
 * @return string A renderable html table.
 */
function socialprofiledata_show($array, $sub_table = FALSE) {
  $style = '';
  $output = '<table id="sociallogin_userprofile_table"  cellspacing="0" style="word-break: break-all;">';
  $count = 1;

  if ($sub_table) {      
    $output .= '<tfoot>';

    foreach ($array as $temp) {
      if (($count % 2) == 0) {
        $style = 'style="background-color:#fcfcfc"';
      }
      foreach ($temp as $key => $val) {
        $output .= '<tr ' . $style . '>';

        if ($key == 'user_id') {
          continue;
        }
        else {
          $output .= '<th scope="col" class="manage-colum">' . ucfirst($key) . '</th>';

          if ($key == 'picture' && !empty($val)) {
            $output .= '<th scope="col" class="manage-colum"><img height="60" width="60" src= "' . (isset($val) ? $val : '') . '" /></th>';
          }
          else {
            $output .= '<th scope="col" class="manage-colum">' . ucfirst($val) . '</th>';
          }
        }

        $output .= '</tr>';
      }

      $count++;
    }
  }
  else {  
    $output .= '<thead><tr>';

    foreach ($array as $key) {
      foreach (array_keys((array) $key) as $value) {
        if ($value == 'user_id' || $value == 'provider') {
          continue;
        }

        $value = str_replace('_', ' ', $value);
        $output .= '<th scope="col"><strong>' . ucfirst($value) . '</strong></th>';
      }
      break;
    }

    $output .= '</tr>
      </thead>
     <tfoot>';

    foreach ($array as $contact) {
      if (($count % 2) == 0) {
        $style = 'style="background-color:#fcfcfc"';
      }
      $output .= '<tr ' . $style . '>';

      foreach ($contact as $key => $val) {
        if ($key == 'user_id' || $key == 'provider') {
          continue;
        }
        elseif ($key == 'provider_access_token') {
          $val = unserialize($val);
        }
        elseif ($key == 'company' && $val != NULL && $val != '') {
          // Companies.
          $companies_result = $this->connection->query("SELECT * FROM {loginradius_companies} WHERE id = :uid", array(':uid' => $val));
          $companies = $companies_result->fetchAll();
       
          if (count($companies) > 0) {  
            $output .= '<th scope="col" class="manage-colum">' . $this->socialprofiledata_show($companies) . '</th>';
            continue;
          }
        }
        else {
          if (!empty($val) && ($key == 'image_url' || $key == 'picture')) {
            $val = '<img height="50" width="50" src= "' . (isset($val) ? $val : '') . '" />';
          }
          $output .= '<th scope="col" class="manage-colum">' . ucfirst($val) . '</th>';
        }
      }

      $output .= '</tr>';
      $count++;
    }
  }

  $output .= '</tfoot></table>';
  return $output;
}


}
