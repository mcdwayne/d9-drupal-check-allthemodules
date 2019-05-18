<?php

namespace Drupal\mailcamp;

/**
 * Class MailcampService.
 *
 * @package Drupal\mailcamp
 */
class MailcampService implements MailcampServiceInterface {
  public $url;
  public $username;
  public $usertoken;

  /**
   * Constructor, retrieves credentials.
   */
  public function __construct() {
    $config = \Drupal::config('mailcamp.settings');

    $this->url = $config->get('mailcamp_url');
    $this->username = $config->get('mailcamp_username');
    $this->usertoken = $config->get('mailcamp_usertoken');
  }

  /**
   * We make our API calls using this function.
   *
   * @param \SimpleXMLElement $request
   *   An xml request without credentials.
   *
   * @return array|bool|string
   *   array = success(data), bool = no response, string = error
   */
  private function doRequest(\SimpleXMLElement $request) {
    $request->addChild('username', $this->username);
    $request->addChild('usertoken', $this->usertoken);

    $xml = $request->asXML();
    $ch = curl_init($this->url);

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $xml,
    ]);
    $result = @curl_exec($ch);

    if ($result !== FALSE) {
      $xml_doc = simplexml_load_string($result);
      $array = json_decode(json_encode($xml_doc->data), TRUE);

      $status = (string) $xml_doc->status;
      if ($status == 'SUCCESS') {
        return (array) $array;
      }
      elseif ($status == 'FAILED') {
        return (string) $xml_doc->errormessage;
      }
    }
    return FALSE;
  }

  /**
   * Retrieves mailing lists available to user.
   */
  public function getMailingLists() {
    $request = new \SimpleXMLElement('<xmlrequest></xmlrequest>');
    $request->addChild('requesttype', 'user');
    $request->addChild('requestmethod', 'GetLists');
    $request->addChild('details', ' ');

    return $this->doRequest($request);
  }

  /**
   * Returns a nice array of mailing list names keyed by id.
   */
  public function getMailingListNames() {
    $original_lists = $this->getMailingLists();
    if (!is_array($original_lists)) {
      return FALSE;
    }
    $lists = [];
    foreach ($original_lists['item'] as $original_list) {
      $lists[$original_list['listid']] = $original_list['name'];
    }
    return $lists;
  }

  /**
   * Retrieves an array of fields associated with specified lists.
   */
  public function getCustomFields(array $lists) {
    $request = new \SimpleXMLElement('<xmlrequest></xmlrequest>');
    $request->addChild('requesttype', 'lists');
    $request->addChild('requestmethod', 'GetCustomFields');

    $details = $request->addChild('details', '');
    foreach ($lists as $listid) {
      $details->addChild('listids', $listid);
    }

    $response = $this->doRequest($request);
    $fields = [];
    foreach ($response['item'] as $field) {
      $fields[$field['fieldid']] = $field;
    }
    return $fields;
  }

  /**
   * Returns a nice array of field names keyed by id.
   */
  public function getCustomFieldNames($lists) {
    $customfields = $this->getCustomFields($lists);
    $names = [];
    foreach ($customfields as $field) {
      $names[$field['fieldid']] = $field['name'];
    }
    return $names;
  }

  /**
   * Adds a subscriber to the specified lists.
   */
  public function addSubscriber(array $lists, array $values) {
    $email = (string) $values['email_address'];
    $request = new \SimpleXMLElement('<xmlrequest></xmlrequest>');
    $request->addChild('requesttype', 'subscribers');
    $request->addChild('requestmethod', 'AddSubscriberToList');

    $details = $request->addChild('details', '');
    $details->addChild('emailaddress', $email);
    $custom_fields = $details->addChild('customfields', '');
    $fields = $this->getCustomFields($lists);

    foreach ($values['customfields'] as $field_id => $value) {
      $field = $fields[$field_id];
      if ($field['fieldtype'] == 'dropdown') {
        $fieldsettings = unserialize($field['fieldsettings']);
        $value = $fieldsettings['Value'][(int) $value];
      }

      $item = $custom_fields->addChild('item', '');
      $item->addChild('fieldid', $field_id);
      $item->addChild('value', $value);
    }

    $details->addChild('mailinglist', '');

    foreach ($lists as $list) {
      $details->mailinglist = $list;
      $this->doRequest($request);
    }
  }

}
