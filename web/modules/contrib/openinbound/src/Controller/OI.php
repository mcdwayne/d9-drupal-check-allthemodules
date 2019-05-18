<?php

namespace Drupal\openinbound\Controller;

define('OI_BACKEND_URL', 'https://api.openinbound.com');


class OI {

    var $api_key = '';
    var $tracking_id = '';

    public final function __construct($tracking_id, $api_key)
    {
        $this->tracking_id = $tracking_id;
        $this->api_key = $api_key;
    }

    public function getStats() {
        $url = OI_BACKEND_URL.'/api/v1/stats?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $array = json_decode(file_get_contents($url));
        return (array)$array->data;
    }

    /**
     * CONTACT API
     */

    /**
     * Load multiple contacts with parameters
     * @param $params
     * @return mixed
     */
    public function queryContacts($params) {
        $get_params = '';
        foreach ($params as $key=>$value) {
            $get_params .= '&'.$key.'='.$value;
        }
        $url = OI_BACKEND_URL.'/api/v1/contact?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key.'&limit=100&'.$get_params;
        //debug_raw($url);
        $array = json_decode(file_get_contents($url));
        return $array;
    }

    /**
     * Load full contact details
     * @param $id
     * @return mixed
     */
    public function getContact($id) {
        $array = json_decode(file_get_contents(OI_BACKEND_URL.'/api/v1/contact/'.$id.'?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key));
        return $array;
    }

    /**
     * Add a new contact to the backend
     * @param $params
     */
    public function addContact($params) {
        $params['tracking_id'] = $this->tracking_id;
        $post_json = json_encode($params);
        $endpoint = OI_BACKEND_URL.'/api/v1/contact?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }

    /**
     * Update a new contact to the backend
     * @param $params
     */
    public function updateContact($id, $properties) {
        $post_json = json_encode($properties);
        $endpoint = OI_BACKEND_URL.'/api/v1/contact/'.$id.'?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }

    /**
     * CONTACT NOTE API
     */

    /**
     * Load multiple contact notes with parameters
     * @param $params
     * @return mixed
     */
    public function queryContactNotes($params) {
        $get_params = '';
        foreach ($params as $key=>$value) {
            $get_params .= '&'.$key.'='.$value;
        }
        $url = OI_BACKEND_URL.'/api/v1/contact_note?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key.'&limit=100&'.$get_params;
        $array = json_decode(file_get_contents($url));
        return $array;
    }

    /**
     * Load full contact note details
     * @param $id
     * @return mixed
     */
    public function getContactNote($id) {
        $array = json_decode(file_get_contents(OI_BACKEND_URL.'/api/v1/contact_note/'.$id.'?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key));
        return $array;
    }

    /**
     * Add a new contact note to the backend
     * @param $params
     */
    public function addContactNote($params) {
        $params['tracking_id'] = $this->tracking_id;
        $post_json = json_encode($params);
        $endpoint = OI_BACKEND_URL.'/api/v1/contact_note?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }

    /**
     * Update a new contact note to the backend
     * @param $params
     */
    public function updateContactNote($id, $properties) {
        $post_json = json_encode($properties);
        $endpoint = OI_BACKEND_URL.'/api/v1/contact_note/'.$id.'?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }


    /**
     * EVENT API
     */

    public function queryEvents($params) {
        $get_params = '';
        foreach ($params as $key=>$value) {
            $get_params .= '&'.$key.'='.$value;
        }
        $url = OI_BACKEND_URL.'/api/v1/event?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key.'&limit=100&'.$get_params;
        $array = json_decode(file_get_contents($url));
        return $array;
    }

    /**
     * Add a new event note to the backend
     * @param $params
     */
    public function addEvent($id, $properties) {
        $properties['contact_id'] = $id;
        $properties['tracking_id'] = $this->tracking_id;
        $properties['api_key'] = $this->tracking_id;
        $post_json = json_encode($properties);
        $endpoint = OI_BACKEND_URL.'/api/v1/event?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }



    /**
     * MAILINGS API
     */
    public function queryMailings($params) {
        $get_params = '';
        foreach ($params as $key=>$value) {
            $get_params .= '&'.$key.'='.$value;
        }
        $url = OI_BACKEND_URL.'/api/v1/mailing?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key.'&limit=100&'.$get_params;
        $array = json_decode(file_get_contents($url));
        return $array;
    }


    public function getMailing($id) {
        $array = json_decode(file_get_contents(OI_BACKEND_URL.'/api/v1/mailing/'.$id.'?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key.'&limit=10'));
        return $array;
    }

    public function addMailing($params) {
        $params['tracking_id'] = $this->tracking_id;
        $post_json = json_encode($params);
        $endpoint = OI_BACKEND_URL.'/api/v1/mailing?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }

    public function updateMailing($id, $properties) {
        $post_json = json_encode($properties);
        $endpoint = OI_BACKEND_URL.'/api/v1/mailing/'.$id.'?tracking_id='.$this->tracking_id.'&api_key='.$this->api_key;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }

    public function addTagToContact($id_contact, $tagtitle) {
        $array = json_decode(file_get_contents(OI_BACKEND_URL . '/api/v1/tagcontact/addbytagtitle/' . $tagtitle . '/'.$id_contact.'?tracking_id=' . $this->tracking_id . '&api_key=' . $this->api_key));
        return $array;
    }
}