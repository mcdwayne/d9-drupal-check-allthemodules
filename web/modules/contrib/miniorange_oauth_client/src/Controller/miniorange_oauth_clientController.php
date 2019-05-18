<?php
 /**
 * @file
 * Contains \Drupal\miniorange_oauth_client\Controller\DefaultController.
 */

namespace Drupal\miniorange_oauth_client\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\miniorange_oauth_client\handler;
class miniorange_oauth_clientController extends ControllerBase {
  public function miniorange_oauth_client_mo_login()
  {
    $code = $_GET['code'];
    $code = Html::escape($code);
   // $code = Utility::check_plain($code);
    $state = $_GET['state'];
    $state = Html::escape($state);
    //$state = SafeMarkup::check_plain($state);

    if( isset( $code) && isset($state ) )
    {
		  if(session_id() == '' || !isset($_SESSION))
				session_start();
			  if (!isset($code))
        {
			    	if(isset($_GET['error_description']))
					      exit($_GET['error_description']);
				    else if(isset($_GET['error']))
					      exit($_GET['error']);
				  exit('Invalid response');
			  }
        else
        {
				  $currentappname = "";
				  if (isset($_SESSION['appname']) && !empty($_SESSION['appname']))
				    $currentappname = $_SESSION['appname'];
				  else if (isset($state) && !empty($state))
            {
					    $currentappname = base64_decode($state);
				    }
          if (empty($currentappname)) {
						exit('No request found for this application.');
					}
        }
  }

     // Getting Access Token
        $app = array();
        $app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_appval');
        $name_attr = "";
        $email_attr = "";
        $name = "";
        $email ="";
		if(isset($app['miniorange_oauth_client_email_attr'])){
		  $email_attr = $app['miniorange_oauth_client_email_attr'];
        }
		if(isset($app['miniorange_oauth_client_name_attr']))
        {
            $name_attr = $app['miniorange_oauth_client_name_attr'];
        }

        $accessToken = self::getAccessToken($app['access_token_ep'], 'authorization_code',

        $app['client_id'], $app['client_secret'], $code, $app['callback_uri']);

        if(!$accessToken)
        {
            print_r('Invalid token received.');
            exit;
        }

    $resourceownerdetailsurl = $app['user_info_ep'];
					if (substr($resourceownerdetailsurl, -1) == "=") {
						$resourceownerdetailsurl .= $accessToken;
					}
					$resourceOwner = self::getResourceOwner($resourceownerdetailsurl, $accessToken);

           /*
            *   Test Configuration
            */
                  if (isset($_COOKIE['Drupal_visitor_mo_oauth_test']) && ($_COOKIE['Drupal_visitor_mo_oauth_test'] == true))
                   {
                    user_cookie_save(array("mo_oauth_test" => false));
                    echo '<style>table{border-collapse: collapse;}table, td, th {border: 1px solid black;padding:4px}</style>';
						echo "<h2>Test Configuration</h2><table><tr><th>Attribute Name</th><th>Attribute Value</th></tr>";
						self::testattrmappingconfig("",$resourceOwner);
						echo "</table>";
						exit();
                    }
           if(!empty($email_attr))
						$email = self::getnestedattribute($resourceOwner, $email_attr);          //$resourceOwner[$email_attr];
					if(!empty($name_attr))
						$name = self::getnestedattribute($resourceOwner, $name_attr);          //$resourceOwner[$name_attr];
                //Attributes not mapped check
				if(empty($email))
                {
						echo "Email address not received. Check your <b>Attribute Mapping<b> configuration.";
                 }
				 $account ='';
                  if(!empty($email))
                    $account = user_load_by_mail($email);
                  if($account == null)
                  {
                    if(!empty($name) && isset($name))
                    $account = user_load_by_name($name);
                  }
     global $base_url;
	   global $user;
     $mo_count = "";
     if(empty($name))
     {
       $name = $email;
     }
      // Create user if not already present.
    if (!isset($account->uid)) {
      print_r("The free version of the module does not support auto creation of users. Please upgrade to the premium version of the plugin to get this feature.");exit;
    }

    $user = \Drupal\user\Entity\User::load($account->id());

    $edit = array();
    if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
      $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    else
      $baseUrlValue = $base_url;
    $edit['redirect'] = $baseUrlValue;
		user_login_finalize($account);
    $response = new RedirectResponse($edit['redirect']);
    $response->send();
    }

    public function getAccessToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url) {

      $ch = curl_init($tokenendpoint);
       curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
       curl_setopt( $ch, CURLOPT_ENCODING, "" );
       curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
       curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
       curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
       curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
         'Authorization: Basic '.base64_encode($clientid.":".$clientsecret),
         'Accept: application/json'
       ));

       curl_setopt( $ch, CURLOPT_POSTFIELDS, 'redirect_uri='.urlencode($redirect_url).'&grant_type='.$grant_type.'&client_id='.$clientid.'&client_secret='.$clientsecret.'&code='.$code);
       $content = curl_exec($ch);
       
       if(curl_error($ch)){
         echo "<b>Response : </b><br>";print_r($content);echo "<br><br>";
         exit( curl_error($ch) );
       }

       if(!is_array(json_decode($content, true))){
         echo "<b>Response : </b><br>";print_r($content);echo "<br><br>";
         exit("Invalid response received.");
       }

       $content = json_decode($content,true);

       if(isset($content["error_description"])){
         exit($content["error_description"]);
       } else if(isset($content["error"])){
         exit($content["error"]);
       } else if(isset($content["access_token"])) {
         $access_token = $content["access_token"];
       } else {
         exit('Invalid response received from OAuth Provider. Contact your administrator for more details.');
       }

       return $access_token;
     }

    public function getResourceOwner($resourceownerdetailsurl, $access_token){

       $ch = curl_init($resourceownerdetailsurl);
       curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
       curl_setopt( $ch, CURLOPT_ENCODING, "" );
       curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
       curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
       curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
       curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
       curl_setopt( $ch, CURLOPT_POST, false);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
           'Authorization: Bearer '.$access_token
           ));

       $content = curl_exec($ch);
       if(curl_error($ch)){
         exit( curl_error($ch) );
       }

       if(!is_array(json_decode($content, true))) {
         exit("Invalid response received.");
       }

       $content = json_decode($content,true);
       if(isset($content["error_description"])){
         if(is_array($content["error_description"]))
           print_r($content["error_description"]);
         else
           echo $content["error_description"];
         exit;
       } else if(isset($content["error"])){
         if(is_array($content["error"]))
           print_r($content["error"]);
         else
           echo $content["error"];
         exit;
       }

       return $content;

     }

function testattrmappingconfig($nestedprefix, $resourceOwnerDetails){

  foreach($resourceOwnerDetails as $key => $resource){
    if(is_array($resource) || is_object($resource)){
      if(!empty($nestedprefix))
        $nestedprefix .= ".";
      self::testattrmappingconfig($nestedprefix.$key,$resource);
    } else {
      echo "<tr><td>";
      if(!empty($nestedprefix))
        echo $nestedprefix.".";
      echo $key."</td><td>".$resource."</td></tr>";
    }
  }
}

function getnestedattribute($resource, $key){
  if(empty($key))
    return "";

  $keys = explode(".",$key);
  if(sizeof($keys)>1){
    $current_key = $keys[0];
    if(isset($resource[$current_key]))
      return self::getnestedattribute($resource[$current_key], str_replace($current_key.".","",$key));
  } else {
    $current_key = $keys[0];
    if(isset($resource[$current_key]))
      return $resource[$current_key];
  }
}

     public function mo_oauth_client_initiateLogin() {
      global $base_url;
        $app_name = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
        $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
        $client_secret = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_secret');
        $scope = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_scope');
        $authorizationUrl =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_authorize_endpoint');
        $callback_uri = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_callback_uri');
        $state = base64_encode($app_name);
      if (strpos($authorizationUrl,'?') !== false) {
      $authorizationUrl =$authorizationUrl. "&client_id=".$client_id."&scope=".$scope."&redirect_uri=".$callback_uri."&response_type=code&state=".$state;
      } else {
      $authorizationUrl =$authorizationUrl. "?client_id=".$client_id."&scope=".$scope."&redirect_uri=".$callback_uri."&response_type=code&state=".$state;
      }

      $_SESSION['oauth2state'] = $state;
      $_SESSION['appname'] = $app_name;
        header('Location: ' . $authorizationUrl);
      $response = new RedirectResponse($authorizationUrl);
      $response->send();
    }

     public function test_mo_config()
     {
       user_cookie_save(array("mo_oauth_test" => true));
       self::mo_oauth_client_initiateLogin();
     }


     public function miniorange_oauth_client_mologin()
     {
      self::mo_oauth_client_initiateLogin();
     }
}