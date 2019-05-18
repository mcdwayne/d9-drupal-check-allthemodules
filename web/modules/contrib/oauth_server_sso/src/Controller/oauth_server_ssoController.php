<?php /**
 * @file
 * Contains \Drupal\oauth_server_sso\Controller\DefaultController.
 */

namespace Drupal\oauth_server_sso\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
//namespace Drupal\oauth_server_sso
use Drupal\oauth_server_sso\DBQueries;
use Drupal\oauth_server_sso\handler;
class oauth_server_ssoController extends ControllerBase {
    public function oauth_server_sso_authorize()
    {
      global $base_url;
      if(\Drupal::currentUser()->isAuthenticated())
      {
        $user = \Drupal::currentUser();
      $does_exist = DBQueries::get_user_id($user);

      if($does_exist == FALSE)
      {
        DBQueries::insert_user_in_table($user);
      }
        $client_id = $_REQUEST['client_id'];
        $redirect_url = $_REQUEST['redirect_uri'];
        $state = $_REQUEST['state'];

        handler::oauth_server_sso_validate_clientId($client_id);
        handler::oauth_server_sso_validate_redirectUrl($redirect_url);
        $code = handler::generateRandom(16);
        $num_updated = DBQueries ::insert_code_from_user_id($code, $user);
        if(!empty(\Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_redirect_url')))
        {
          $url = \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_redirect_url');
          if (strpos($url,'?') !== false) {
            $url =$url.'&code='.$code."&state=".$state;
          }
          else{
            $url =$url.'?code='.$code."&state=".$state;
          }
          $code_time = time();
          $authCodeTime = DBQueries ::insert_code_expiry_from_user_id($code_time,$user);
          $response = new RedirectResponse($url);
	        $response->send();
        }
        else{
          echo "Redirect URL not configured.";
        }
      }
      else{
        $rem_val = $_SERVER['QUERY_STRING'];
        $redirecting_url = $base_url.'/authorize?'.$rem_val;
        $array_form = explode(' ', $redirecting_url);
        \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_red',$redirecting_url)->save();

        user_cookie_save(['redirecting_url' => $redirecting_url]);
        $response = new RedirectResponse('user/login');
	      $response->send();

      }
    }
    public function oauth_server_sso_access_token()
    {
      $request_code = $_REQUEST['code'];
      $redirect_uri = $_REQUEST['redirect_uri'];
      $code = DBQueries ::get_same_code_as_received($request_code);
      if($code !='')
      {
        $user_id = DBQueries ::get_code_from_user_id($request_code);
        $client_id = $_REQUEST['client_id'];
        handler::oauth_server_sso_validate_clientId($client_id);
        $client_secret = $_REQUEST['client_secret'];
        handler::oauth_server_sso_validate_clientSecret($client_secret);

      $code_request_time = $user_id['auth_code_expiry_time'];

        $grant_type = $_REQUEST['grant_type'];

        handler::oauth_server_sso_validate_code($code,$request_code,$code_request_time);
        handler::oauth_server_sso_validate_grant($grant_type);
        handler::oauth_server_sso_validate_redirectUrl($redirect_uri);

        $access_token = handler::generateRandom(255);

        $access_token_inserted = DBQueries ::insert_access_token_with_user_id($user_id['user_id_val'], $access_token);

        $url = $_REQUEST['redirect_uri'];
        if (strpos($url,'?') !== false) {
	        $url =$url.'&access_token='.$access_token."&expires_in=900&token_type=Bearer&scope=profile";
	      }
        else {
          $url =$url.'?access_token='.$access_token."&expires_in=900&token_type=Bearer&scope=profile";
	      }
        $arr = array('access_token' => $access_token, 'expires_in' => $expires_in, 'token_type' => 'Bearer', 'scope' => 'profile');
        $req_time = time();
        $accessToken_expiry_time_inserted = DBQueries::insert_access_token_expiry_time($user_id['user_id_val'],$req_time);
        echo json_encode($arr);exit;
      }
      else{
        print_r('Code missing');exit;
      }
    }
    public function oauth_server_sso_user_info()
    {
      $access_values = array();
      foreach (getallheaders() as $name => $value) {
        $access_values[$name] = $value;
      }
      $string_full = $access_values['Authorization'];
      $access_token_received = trim(substr($string_full, 6));

      $user_id = DBQueries::get_user_id_from_access_token($access_token_received);

      $req_time = DBQueries::get_access_token_request_time_from_user_id($user_id['user_id_val']);

      if(!empty($user_id))
      {
        handler::ValidateAccessToken($req_time['access_token'], $req_time['access_token_request_time']);
      }
      else{
        echo "Access Token could not be retreived. Please try again or contact your administrator";exit;
      }
      $user_id = $user_id['user_id_val'];
      $user_obj = User::load($user_id);
      $user_id = $user_obj->id();

      $genericObject = (object) array(
    'uid'=>$user_obj-> id(),
    'name'=>$user_obj-> getUsername(),
    'mail'=>$user_obj-> getEmail(),
    'roles'=>$user_obj-> getRoles(),
    );
      echo json_encode($genericObject);exit;
    }
}