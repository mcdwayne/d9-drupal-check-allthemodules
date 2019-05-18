<?php
namespace Drupal\oauth_server_sso;
    class handler{
    static function generateRandom($length=30) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$charactersLength = strlen($characters);
		$randomString = '';

        for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
    }

    static function oauth_server_sso_validate_code($code, $request_code,$request_time)
    {
      $current_time = time();
      if($current_time - $request_time >=400)
      {
        echo "Your authentication code has expired. Please try again.";exit;
      }
      if($code == $request_code)
        {
            //variable_set('oauth_server_sso_code','');
            \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_code','')->save();
        }
        else{
            print_r("Incorrect Code");exit;
        }

    }
    static function ValidateAccessToken($accessToken, $request_time)
    {
      $current_time = time();

      if($current_time - $request_time >=900)
      {
        echo "Your access token has expired. Please try again.";exit;
      }

    }
    static function oauth_server_sso_validate_clientSecret($client_secret)
    {
      //$secret_stored = variable_get('oauth_server_sso_client_secret','');
      $secret_stored = \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_client_secret');

      if($secret_stored != '')
      {
        if($client_secret != $secret_stored)
        {
          print_r('Client Secret mismatch');exit;
        }
      }
      else{
        print_r('Client Secret is not configured');exit;
      }
    }
    static function oauth_server_sso_validate_grant($grant_type)
    {
        if($grant_type != "authorization_code")
        {
            print_r("Only Authorization Code grant type supported");exit;
        }
    }
    static function oauth_server_sso_validate_clientId($client_id)
    {
      $id_stored = \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_client_id');
      // variable_get('oauth_server_sso_client_id','');
      if($id_stored != '')
      {
        if($client_id != $id_stored)
        {
          print_r('Client ID mismatch');exit;
        }
      }
      else{
        print_r('Client ID is not configured');exit;
      }
    }

    static function oauth_server_sso_validate_redirectUrl($redirect_uri)
    {
      $redirect_url_stored = \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_redirect_url');

     // variable_get('oauth_server_sso_redirect_url','');
      if($redirect_url_stored != '')
      {
        if($redirect_uri != $redirect_url_stored)
        {
          print_r('Redirect URL mismatch');exit;
        }
      }
      else{
        print_r('Redirect URL is not configured');exit;
      }
    }


}

?>