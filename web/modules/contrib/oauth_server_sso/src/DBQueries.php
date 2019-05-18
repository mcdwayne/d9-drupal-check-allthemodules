<?php
namespace Drupal\oauth_server_sso;
class DBQueries{

    public static function get_user_id($user)
    {
       $user_id = $user->id();

        /*  $connection = \Drupal::database();
          $query = $connection->query("SELECT * FROM oauth_server_sso_token where user_id_val = '$user_id'");
          $does_exist = $query->fetchAssoc();
          return $does_exist;
        */
        $connection = \Drupal::database();
        $query = $connection->query("SELECT * FROM oauth_server_sso_token where user_id_val = $user_id");
        $query->allowRowCount = TRUE;
        if($query->rowCount()>0){
            return TRUE;
        }
          return FALSE;
    }
    public static function insert_user_in_table($user)
    {
       /* $connection = \Drupal::database();
        $result = $connection->insert('oauth_server_sso_token')
            ->fields([
                'user_id_val' => $user->id(),
                ])
            ->execute();
            */
        $user_id = $user->id();
        $database = \Drupal::database();
        $fields = array(
            'user_id_val' => $user_id,
         //   'configured_auth_methods' => $auth_method,
           // 'miniorange_registered_email' => $username,
        );
     $database->insert('oauth_server_sso_token')->fields($fields)->execute();
    }
    public static function update_user_in_table($user)
    {
       // db_update('oauth_server_sso_token')
       $connection = \Drupal::database();
       $result = $connection->update('oauth_server_sso_token')
       -> fields([
        'user_id_val' => $user->uid,
        ])
        ->execute();
    }
    public static function insert_code_from_user_id($code, $user)
    {
        $user_id = $user->id();
        $connection = \Drupal::database();
        $num_updated = $connection->update('oauth_server_sso_token')
        ->fields(['auth_code'=> $code])
        ->condition('user_id_val', $user_id,'=')
        ->execute();
        return $num_updated;
    }
    public static function get_code_from_user_id($request_code)
    {
        $data_value = array('user_id_val');

        $connection = \Drupal::database();
        $query = $connection->query("SELECT * FROM oauth_server_sso_token where auth_code = '$request_code'");
        $user_id = $query->fetchAssoc();

        return $user_id;
    }
    public static function insert_code_expiry_from_user_id($code_time,$user)
    {
       // $authCodeTime = db_update('oauth_server_sso_token')
       $user_id = $user->id();
       $connection = \Drupal::database();
       $authCodeTime = $connection->update('oauth_server_sso_token')
       ->fields(['auth_code_expiry_time'=> $code_time])
       ->condition('user_id_val', $user_id,'=')
       ->execute();
     /*
       $connection = \Drupal::database();
       $authCodeTime = $connection->update('oauth_server_sso_token')
          ->fields([
            'auth_code_expiry_time' => $code_time,
          ])
            ->condition('user_id_val', $user->uid, '=')
            ->execute();
*/
        return $authCodeTime;
    }
    public static function get_same_code_as_received($request_code)
    {
      $connection = \Drupal::database();
      $query = $connection->query("SELECT * FROM oauth_server_sso_token where auth_code = '$request_code'");
      $code = $query->fetchAssoc();
      $code = $code['auth_code'];
      return $code;
    }
    public static function insert_access_token_with_user_id($user_id, $access_token)
    {
        $connection = \Drupal::database();
        $access_token_inserted = $connection->update('oauth_server_sso_token')
        ->fields(['access_token'=> $access_token])
        ->condition('user_id_val', $user_id,'=')
        ->execute();


/*
      //  $access_token_inserted = db_update('oauth_server_sso_token')
      $connection = \Drupal::database();
      $access_token_inserted = $connection->update('oauth_server_sso_token')
      ->fields([
          'access_token' => $access_token,
        ])
        ->condition('user_id_val', $user_id, '=')
        ->execute();
        */
        return $access_token_inserted;
    }
    public static function insert_access_token_expiry_time($user_id,$req_time)
    {
        $connection = \Drupal::database();
        $accessToken_expiry_time_inserted = $connection->update('oauth_server_sso_token')
        ->fields(['access_token_request_time'=> $req_time])
        ->condition('user_id_val', $user_id,'=')
        ->execute();

        /*
       // $accessToken_expiry_time_inserted = db_update('oauth_server_sso_token')
        $connection = \Drupal::database();
      $accessToken_expiry_time_inserted = $connection->update('oauth_server_sso_token')
      ->fields([
          'access_token_request_time' => $req_time,
      ])
        ->condition('user_id_val', $user_id, '=')
        ->execute();
        */

        return $accessToken_expiry_time_inserted;
    }
    public static function get_user_id_from_access_token($access_token_received)
    {
        $data_value = array('user_id_val');

        $connection = \Drupal::database();
        $query = $connection->query("SELECT * FROM oauth_server_sso_token where access_token = '$access_token_received'");
        $user_id = $query->fetchAssoc();

        return $user_id;
    }
    public static function get_access_token_request_time_from_user_id($user_id)
    {
        $connection = \Drupal::database();
        $query = $connection->query("SELECT * FROM oauth_server_sso_token where user_id_val = '$user_id'");
        $req_time = $query->fetchAssoc();

        return $req_time;
    }
}
?>