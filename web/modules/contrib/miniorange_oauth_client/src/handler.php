<?php
namespace Drupal\miniorange_oauth_client;
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

    static function miniorange_oauth_client_validate_code($code, $request_code,$request_time)
    {
      $current_time = time();
      if($current_time - $request_time >=400)
      {
        echo "Your authentication code has expired. Please try again.";exit;
      }
      if($code == $request_code)
        {
            //variable_set('miniorange_oauth_client_code','');
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_code','')->save();
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
    static function miniorange_oauth_client_validate_clientSecret($client_secret)
    {
      $secret_stored = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_client_secret');

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
    static function miniorange_oauth_client_validate_grant($grant_type)
    {
        if($grant_type != "authorization_code")
        {
            print_r("Only Authorization Code grant type supported");exit;
        }
    }
    static function miniorange_oauth_client_validate_clientId($client_id)
    {
      $id_stored = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_client_id');
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

    static function miniorange_oauth_client_validate_redirectUrl($redirect_uri)
    {
      $redirect_url_stored = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_redirect_url');
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