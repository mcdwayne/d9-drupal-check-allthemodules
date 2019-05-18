<?php 

namespace Drupal\ae\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\user\Entity\User;

class LoginController extends ControllerBase {

    public function createuser($aeid, $createlocal, $signinlocal) {

        $createLocalUser = $createlocal == "true";
        $signinLocalUser = $signinlocal == "true";

        $ae_user = $this->fetch_ae_user($aeid);

        if($this->drupal_user_exists($aeid)) {
            $drupal_user = $this->fetch_drupal_user($aeid);
        }
        else if($this->returning_user_with_different_aeid($ae_user)) {
            $drupal_user = $this->fetch_returning_user($ae_user);
        }
        else {
            if($createLocalUser) {
                $drupal_user = $this->create_new_drupal_user($ae_user);
                $this->create_local_ae_user($drupal_user, $ae_user);
            }
        }
        $this->add_services_for_user($drupal_user, $ae_user);

        if($signinLocalUser)
            $this->sign_in_local_drupal_user($drupal_user);

        exit(0);
    }

    private function fetch_ae_user($aeid) {
        $state = \Drupal::state();
        $client = \Drupal::httpClient();

        $api_key = $state->get('api_key');
        $url = "https://staging.theappreciationengine.com/v1.1/member/" . $aeid . "?apiKey=" . $api_key;

        //$url = "https://akshay.dev.appreciationengine.com/v1.1/member/4290847?apiKey=9ee609a0370231ac93149413e00a2ca0";
        $request = $client->get($url);
        $ae_user = $request->getBody();
        $user_json = json_decode($ae_user);
        return $user_json;
    }

    private function drupal_user_exists($aeid) {
        $uid = $this->fetch_uid_from_aeid($aeid);
        return is_numeric($uid);
    }

    private function fetch_drupal_user($aeid) {
        $uid = $this->fetch_uid_from_aeid($aeid);
        $drupal_user = \Drupal\user\Entity\User::load($uid);
        $drupal_user->activate();
        $drupal_user->save();
        return $drupal_user;
    }

    private function returning_user_with_different_aeid($ae_user) {

        foreach ($ae_user->services as $service) {
            $aeid = db_query("SELECT aeid FROM {ae_services} WHERE serviceID = :sid", [':sid' => $service->ID])->fetchField();
            if(is_numeric($aeid)) {
                return true;
            }
        }
        return false;

    }

    private function fetch_returning_user($ae_user) {
        foreach ($ae_user->services as $service) {
            $aeid = db_query("SELECT aeid FROM {ae_services} WHERE serviceID = :sid", [':sid' => $service->ID])->fetchField();
            if(isset($aeid)) {
                $drupal_user = $this->fetch_drupal_user($aeid);
                $drupal_user->activate();
                $drupal_user->save();
                return $drupal_user;
            }
        }
    }

    private function create_new_drupal_user($ae_user) {
        $drupal_user = User::create();
        $drupal_user->setPassword('password');
        $drupal_user->enforceIsNew();
        $drupal_user->setEmail($ae_user->data->Email);
        $drupal_user->setUsername($ae_user->data->Username);
        $drupal_user->activate();
        $drupal_user->save();
        return $drupal_user;
    }

    private function sign_in_local_drupal_user($drupal_user) {
        user_login_finalize($drupal_user);
    }

    private function create_local_ae_user($drupal_user, $ae_user) {
        $uid = $drupal_user->id();

        db_insert('ae_users')->fields([
            'aeid' => isset($ae_user->data->ID) ? $ae_user->data->ID: "",
            'uid' => $uid,
            'FirstName' => isset($ae_user->data->FirstName) ? $ae_user->data->FirstName: "",
            'SurName' => isset($ae_user->data->SurName) ? $ae_user->data->SurName : "",
            'Email' => isset($ae_user->data->Email) ? $ae_user->data->Email : "",
            'City' => isset($ae_user->data->City) ? $ae_user->data->City : "",
            'State' => isset($ae_user->data->State) ? $ae_user->data->State : "",
            'Country' => isset($ae_user->data->Country) ? $ae_user->data->Country : "",
            'Postcode' => isset($ae_user->data->PostCode) ? $ae_user->data->PostCode : "",
            'MobilePhone' => isset($ae_user->data->MobilePhone) ? $ae_user->data->MobilePhone : "",
            'Website' => isset($ae_user->data->Website) ? $ae_user->data->Website : "",
            'Username' => isset($ae_user->data->Username) ? $ae_user->data->Username : "",
            'VerifiedEmail' => isset($ae_user->data->VerifiedEmail) ? $ae_user->data->VerifiedEmail : "",
            'Bio' => isset($ae_user->data->Bio) ? $ae_user->data->Bio : "",
            'Gender' => isset($ae_user->data->Gender) ? $ae_user->data->Gender : "",
            'BirthDate' => isset($ae_user->data->BirthDate) ? $ae_user->data->BirthDate : "",
        ])->execute();
    }

    private function add_services_for_user($drupal_user, $ae_user) {

        // for each service for the ae user
        foreach ($ae_user->services as $service) {

            if(!$this->service_exists($service)) {
                db_insert('ae_services')->fields([
                    'serviceID' => isset($service->ID) ? $service->ID : "",
                    'aeid' => isset($ae_user->data->ID) ? $ae_user->data->ID : ""
                    //$drupal_user->id()
                ])->execute();
            }
        }

    }

    private function service_exists($service) {
        $service_id = db_query("SELECT serviceID FROM ae_services WHERE serviceID = :serviceID LIMIT 1;", [':serviceID' => $service->ID])->fetchField();
        return is_numeric($service_id);
    }

    private function fetch_uid_from_aeid($aeid) {
        $uid = db_query("SELECT uid FROM {ae_users} WHERE aeid = :aeid", [':aeid' => $aeid])->fetchField();
        return $uid;
    }









}


?>