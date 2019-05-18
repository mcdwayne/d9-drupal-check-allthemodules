<?php
namespace Drupal\optit\Optit;

class Optit {

  const OPTIT_URL = 'api.optitmobile.com/1';
  const OPTIT_TIME_FORMAT = 'm/d/y h:i A T';

  private $http;

  // This is an ugly buffer, but it will do the trick for drupal's pagination purposes.
  public $totalPages;
  public $currentPage;

  // This is even uglier page property, which allows me not to set page numbers in method parameters.
  private $page = 1;

  public static function create() {
    // @todo: Use dependency injection.
    $config = \Drupal::config('optit.settings');
    // Initiate bridge class and dependencies and get the list of keywords from the API.
    return new self($config->get('username'), $config->get('password'), self::OPTIT_URL);
  }

  public function __construct($username, $password, $apiEndpoint) {
    $this->http = new RESTclient($username, $password, $apiEndpoint);
  }


  // ### Keywords
  // ###

  /**
   * Load a list of all keywords.
   * http://api.optitmobile.com/1/keywords.{format}
   *
   * @todo: replace this array with individual params.
   *
   * @param array $params
   *   Array of all params.
   *
   * @return array
   *   Array of all keyword entities.
   */
  public function keywordsGet($params = NULL) {
    if ($params) {
      $urlParams = $params;
    }
    else {
      $urlParams = [];
    }
    $urlParams['page'] = $this->getPage();

    $response = $this->http->get('keywords', $urlParams);
    $this->collectStats($response);

    $keywords = [];
    if (!empty($response['keywords'])) {
      foreach ($response['keywords'] as $keyword) {
        $keywords[] = Keyword::create($keyword['keyword']);
      }
    }

    return $keywords;
  }

  /**
   * Load a single keyword entity.
   * http://api.optitmobile.com/1/keywords.{format}
   *
   * @param int $keywordId
   *   ID of the keyword.
   *
   * @return Keyword
   *   Keyword entity.
   */
  public function keywordGet($keywordId) {
    $response = $this->http->get("keywords/{$keywordId}");
    return Keyword::create($response['keyword']);
  }

  /**
   * Check if keyword with a given name already exists.
   * http://api.optitmobile.com/1/keyword/exists.{format}
   *
   * @param int $name
   *   Name of the keyword.
   *
   * @return bool
   *   TRUE if keyword exists.
   */
  public function keywordExists($name) {
    $urlParams = [];
    $urlParams['keyword'] = $name;

    $response = $this->http->get("keywords/exists", $urlParams);

    return $response['keyword']['exists'];
  }

  /**
   * Save a new keyword.
   * POST http://api.optitmobile.com/1/keywords.{format}
   *
   * @param Keyword $keyword
   *   Keyword entity.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function keywordCreate(Keyword $keyword) {
    $keyword = $keyword->toArray();
    $keyword['keyword'] = $keyword['keyword_name'];
    unset($keyword['keyword_name']);

    $response = $this->http->post("keywords", NULL, $keyword);

    return $response;
  }

  /**
   * Update an existing keyword.
   * PUT http://api.optitmobile.com/1/keywords/{keyword_id}.{format}
   *
   * @todo: This call does not work yet due to API server error. Recheck after fix.
   *
   * @param int $keywordId
   *   The ID of the keyword.
   * @param Keyword $keyword
   *   Keyword entity (with updated values).
   *
   * @return bool
   *   TRUE if successful.
   */
  public function keywordUpdate($keywordId, Keyword $keyword) {
    // Prepare new keyword for being saved
    $keyword = $keyword->toArray();
    $keyword['keyword'] = $keyword['keyword_name'];
    unset($keyword['keyword_name']);

    $response = $this->http->put("keywords/{$keywordId}", NULL, $keyword);

    return $response;
  }



  // ### Interests
  //

  /**
   * Get a list of interests for a given keyword.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/interests.{format}
   *
   * @param int $keywordId
   *   The ID of the keyword.
   * @param string $name
   *   Name of the interest.
   *
   * @return array
   *   Array of all Interest entities.
   */
  public function interestsGet($keywordId, $name = NULL) {
    $urlParams = [];

    if ($name) {
      $urlParams['name'] = $name;
    }

    $response = $this->http->get("keywords/{$keywordId}/interests", $urlParams);

    $interests = [];
    if (!empty($response['interests'])) {
      foreach ($response['interests'] as $i) {
        $interests[] = Interest::create($i['interest']);
      }
    }

    return $interests;
  }

  /**
   * Get a list of interests filtered by phone number.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions/{phone}/interests.{format}
   *
   * @param int $keywordId
   *   The ID of the keyword.
   * @param string $phone
   *   Phone number.
   *
   * @return array
   *   Array of all Interest entities.
   */
  public function interestsGetByPhone($keywordId, $phone) {
    $response = $this->http->get("keywords/{$keywordId}/subscriptions/{$phone}/interests");

    $interests = [];
    if (!empty($response['interests'])) {
      foreach ($response['interests'] as $i) {
        $interests[] = Interest::create($i['interest']);
      }
    }

    return $interests;
  }

  /**
   * Get an individual interest.
   * http://api.optitmobile.com/1/interests/{interest_id}.{format}
   *
   * @param int $interestId
   *   The ID of the interest.
   *
   * @return Interest
   *   An Interest entity.
   */
  public function interestGet($interestId) {
    $response = $this->http->get("interests/{$interestId}");

    return Interest::create($response['interest']);
  }

  /**
   * Create a new interest.
   */
  public function interestCreate($keywordId, $name, $description = NULL) {
    // @todo: Handle http request failure.

    // Prepare params.
    $postParams = [];
    $postParams['name'] = $name;
    if ($description) {
      $postParams['description'] = $description;
    }

    // Make the request.
    $response = $this->http->post("keywords/{$keywordId}/interests", NULL, $postParams);

    // Return Interest object.
    return Interest::create($response['interest']);
  }


  /**
   * Get a list of subsciptions for an interest.
   * http://api.optitmobile.com/1/interests/{interest_id}/subscriptions.{format}
   *
   * @param NULL $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   * @param NULL $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using
   *   the Get Member method.
   * @param NULL $firstName
   *   first_name - first name of the member
   * @param NULL $lastName
   *   last_name - last name of the member
   * @param NULL $zip
   *   zip - zip code or postal code of the member
   * @param NULL $gender
   *   gender - gender of the member. Values: [male, female]
   * @param NULL $signupDateStart
   *   signup_date_start - yyyymmddhhmmss
   * @param NULL $signupDateEnd
   *   signup_date_end - yyyymmddhhmmss
   *
   * @return mixed
   *   an array of subscription entities.
   *
   * @todo: Reduce duplication of code in interestGetSubscriptions() and subscriptionsGet()
   *
   */
  public function interestGetSubscriptions($interestId, $phone = NULL, $memberId = NULL, $firstName = NULL, $lastName = NULL, $zip = NULL, $gender = NULL, $signupDateStart = NULL, $signupDateEnd = NULL) {

    // Prepare params.
    $urlParams = [];
    $urlParams['page'] = $this->getPage();
    if ($phone) {
      $urlParams['phone'] = $phone;
    }
    if ($memberId) {
      $urlParams['member_id'] = $memberId;
    }
    if ($firstName) {
      $urlParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $urlParams['last_name'] = $lastName;
    }
    if ($zip) {
      $urlParams['zip'] = $zip;
    }
    if ($gender) {
      $urlParams['gender'] = $gender;
    }
    if ($signupDateStart) {
      $urlParams['signup_date_start'] = $signupDateStart;
    }
    if ($signupDateEnd) {
      $urlParams['signup_date_end'] = $signupDateEnd;
    }

    $response = $this->http->get("interests/{$interestId}/subscriptions", $urlParams);
    $this->collectStats($response);

    $subscriptions = [];
    foreach ($response['subscriptions'] as $record) {
      $subscriptions[] = Subscription::create($record['subscription']);
    }

    $this->totalPages = $response['total_pages'];

    return $subscriptions;
  }

  /**
   * Add a subscription to an interest.
   * http://api.optitmobile.com/1/interests/{interest_id}/subscriptions.{format}
   *
   * @param int $interestId
   *   ID of the keyword
   * @param NULL $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. (Phone or member_id is
   *   required)  Example: 12225551212
   * @param NULL $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using the
   *   Get Member method. (Phone or member_id is required)
   *
   * @return bool
   *   TRUE if successful request.
   */
  public function interestSubscribe($interestId, $phone = NULL, $memberId = NULL) {
    if (!$phone && !$memberId) {
      return FALSE;
    }
    $postParams = [];
    if ($phone) {
      $postParams['phone'] = $phone;
    }
    if ($memberId) {
      $postParams['member_id'] = $memberId;
    }

    if ($this->http->post("interests/{$interestId}/subscriptions", NULL, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Delete a subscription from interest.
   * http://api.optitmobile.com/1/interests/{interest_id}/subscriptions/{phone}.{format}
   *
   * @param int $interestId
   *   ID of the keyword
   * @param string $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   *
   *
   * @return bool
   *   TRUE if successful request.
   */
  public function interestUnsubscribe($interestId, $phone) {
    if ($this->http->delete("interests/{$interestId}/subscriptions/{$phone}")) {
      return TRUE;
    }
    return FALSE;
  }


  // ### Members
  //


  /**
   * Get a list of members.
   * http://api.optitmobile.com/1/members.{format}
   */
  public function membersGet($phone = NULL, $firstName = NULL, $lastName = NULL, $zip = NULL, $gender = NULL) {
    // Prepare params.
    $urlParams = [];
    $urlParams['page'] = $this->getPage();
    if ($phone) {
      $urlParams['phone'] = $phone;
    }
    if ($firstName) {
      $urlParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $urlParams['last_name'] = $lastName;
    }
    if ($zip) {
      $urlParams['zip'] = $zip;
    }
    if ($gender) {
      $urlParams['gender'] = $gender;
    }

    $response = $this->http->get('members', $urlParams);
    $this->collectStats($response);

    $members = [];
    if (!empty($response['members'])) {
      foreach ($response['members'] as $record) {
        $members[] = Member::create($record['member']);
      }
    }

    $this->totalPages = $response['total_pages'];

    return $members;
  }


  // ##
  // ## Subscriotions

  /**
   * * Get a list of members.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param NULL $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   * @param NULL $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using
   *   the Get Member method.
   * @param NULL $firstName
   *   first_name - first name of the member
   * @param NULL $lastName
   *   last_name - last name of the member
   * @param NULL $zip
   *   zip - zip code or postal code of the member
   * @param NULL $gender
   *   gender - gender of the member. Values: [male, female]
   * @param NULL $signupDateStart
   *   signup_date_start - yyyymmddhhmmss
   * @param NULL $signupDateEnd
   *   signup_date_end - yyyymmddhhmmss
   *
   * @return mixed
   *   an array of subscription entities.
   *
   * @todo: Reduce duplication with interestGetSubscriptions().
   * @todo: Rename to keywordGetSubscriptions(), standardize!!.
   */
  public function subscriptionsGet($keywordId, $phone = NULL, $memberId = NULL, $firstName = NULL, $lastName = NULL, $zip = NULL, $gender = NULL, $signupDateStart = NULL, $signupDateEnd = NULL) {
    // Prepare params.
    $urlParams = [];
    $urlParams['page'] = $this->getPage();
    if ($phone) {
      $urlParams['phone'] = $phone;
    }
    if ($memberId) {
      $urlParams['member_id'] = $memberId;
    }
    if ($firstName) {
      $urlParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $urlParams['last_name'] = $lastName;
    }
    if ($zip) {
      $urlParams['zip'] = $zip;
    }
    if ($gender) {
      $urlParams['gender'] = $gender;
    }
    if ($signupDateStart) {
      $urlParams['signup_date_start'] = $signupDateStart;
    }
    if ($signupDateEnd) {
      $urlParams['signup_date_end'] = $signupDateEnd;
    }

    if ($response = $this->http->get("keywords/{$keywordId}/subscriptions", $urlParams)) {
      $this->collectStats($response);
      $subscriptions = [];
      if (!empty($response['subscriptions'])) {
        foreach ($response['subscriptions'] as $record) {
          $subscriptions[] = Subscription::create($record['subscription']);
        }
      }
      $this->totalPages = $response['total_pages'];
      return $subscriptions;
    }

    return FALSE;
  }

  /**
   * Get individual subscription
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions/{phone}.{format}
   *
   * @param $keywordId
   *   ID of the keyword
   * @param $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   *
   * @return Subscription object or FALSE if subscription was not present.
   *
   */
  public function subscriptionGetByPhone($keywordId, $phone) {
    if ($response = $this->http->get("keywords/{$keywordId}/subscriptions/{$phone}")) {
      return Subscription::create($response['subscription']);
    }

    return FALSE;
  }

  /**
   * Subscribe a member to a keyword.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param NULL $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. (Phone or member_id is
   *   required)  Example: 12225551212
   * @param NULL $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using the
   *   Get Member method. (Phone or member_id is required)
   * @param NULL $interestId
   *   interest_id - add this user to one or many interests. For multiple interests, please comma separate the
   *   interest_ids. It is the ID attribute in the Interest entity and can be viewed using the Get Interest method.
   * @param NULL $firstName
   *   first_name - first name of the member
   * @param NULL $lastName
   *   last_name - last name of the member
   * @param NULL $address1
   *   address1 - address line 1 of the member
   * @param NULL $address2
   *   address2 - address line 2 of the member
   * @param NULL $city
   *   city - city of the member
   * @param NULL $state
   *   state - state of the member as a two character abbreviation
   * @param NULL $zip
   *   zip - zip code or postal code of the member
   * @param NULL $gender
   *   gender - gender of the member. Values: [male, female]
   * @param NULL $birthDate
   *   birth_date - birthdate in the format yyyymmdd
   * @param NULL $emailAddress
   *   email_address - email address of the member
   *
   * @return bool|Subscription
   *   FALSE if request did not succeed. Otherwise - Subscription object.
   */
  public function subscriptionCreate($keywordId, $phone = NULL, $memberId = NULL, $interestId = NULL, $firstName = NULL, $lastName = NULL, $address1 = NULL, $address2 = NULL, $city = NULL, $state = NULL, $zip = NULL, $gender = NULL, $birthDate = NULL, $emailAddress = NULL) {

    // Validation step.
    if (!$phone && !$memberId) {
      return FALSE;
    }

    // Preparing params.
    $postParams = [];
    if ($phone) {
      $postParams['phone'] = $phone;
    }
    if ($memberId) {
      $postParams['member_id'] = $memberId;
    }
    if ($interestId) {
      $postParams['interest_id'] = $interestId;
    }
    if ($firstName) {
      $postParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $postParams['last_name'] = $lastName;
    }
    if ($address1) {
      $postParams['address1'] = $address1;
    }
    if ($address2) {
      $postParams['address2'] = $address2;
    }
    if ($city) {
      $postParams['city'] = $city;
    }
    if ($state) {
      $postParams['state'] = $state;
    }
    if ($zip) {
      $postParams['zip'] = $zip;
    }
    if ($gender) {
      $postParams['gender'] = $gender;
    }
    if ($birthDate) {
      $postParams['birth_date'] = $birthDate;
    }
    if ($emailAddress) {
      $postParams['email_address'] = $emailAddress;
    }

    if ($response = $this->http->post("keywords/{$keywordId}/subscriptions", NULL, $postParams)) {
      return new Subscription($postParams);
    }
    return FALSE;
  }

  /**
   * Unsubscribe user from all keywords.
   * http://api.optitmobile.com/1/subscription/{phone}.{format}
   *
   * @param $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   *
   * @return bool
   */
  public function subscriptionsCancelAllKeywords($phone) {
    if ($response = $this->http->delete("subscription/{$phone}")) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Unsubscribe member from one keyword.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscription/{phone}.{format}
   *
   * @param string $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   * @param int $keywordId
   *   ID of the keyword
   *
   * @return bool
   */
  public function subscriptionCancelByKeyword($phone, $keywordId) {
    if ($response = $this->http->delete("keywords/{$keywordId}/subscription/{$phone}")) {
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Send a message to one or more users
   * http://api.optitmobile.com/1/sendmessage/keywords.{format}
   *
   * @param string $phone
   *   Single or multiple comma separated phone numbers.
   * @param int $keywordId
   *   ID of the keyword.
   * @param string $title
   *   Title of the message.
   * @param string $message
   *   Message to be set to subscribers.
   *
   * @return bool
   */
  public function messagePhone($phone, $keywordId, $title, $message) {
    $postParams = [];
    $postParams['phone'] = $phone;
    $postParams['keyword_id'] = $keywordId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    if ($response = $this->http->post("sendmessage", NULL, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Send a message to all users subscribed to a given keyword
   * http://api.optitmobile.com/1/sendmessage/keywords.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param string $title
   *   Title of the message
   * @param string $message
   *   Message to be set to subscribers
   *
   * @return bool
   */
  public function messageKeyword($keywordId, $title, $message) {
    $postParams = [];
    $postParams['keyword_id'] = $keywordId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    if ($response = $this->http->post("sendmessage/keywords", NULL, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Send an MMS message to all users subscribed to a given keyword
   * http://api.optitmobile.com/1/sendmms/keywords.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param string $title
   *   Title of the message
   * @param string $message
   *   Message to be set to subscribers
   * @param string $contentUrl
   *   URL to the multimedia entity (image, video, audio)
   *
   * @return bool
   */
  public function messageKeywordMMS($keywordId, $title, $message, $contentUrl = NULL) {
    $postParams = [];
    $postParams['keyword_id'] = $keywordId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    $postParams['content_url'] = $contentUrl;

    if ($response = $this->http->post("sendmms/keywords", NULL, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Send a message to all users subscribed to a given keyword
   * http://api.optitmobile.com/1/sendmessage/keywords.{format}
   *
   * @param int $interestId
   *   ID of the interest
   * @param string $title
   *   Title of the message
   * @param string $message
   *   Message to be set to subscribers
   *
   * @return bool
   */
  public function messageInterest($interestId, $title, $message) {
    $postParams = [];
    $postParams['interest_id'] = $interestId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    if ($response = $this->http->post("sendmessage/interests", NULL, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Opt It SMS Bulk Send Message. Prepares an XML document structured like:
   *  <?xml version="1.0" encoding="UTF-8"?>
   *  <keywords>
   *    <keyword id="20481">
   *      <messages>
   *        <message title="test title" text="test message">
   *          <recipients/>
   *        </message>
   *      </messages>
   *    </keyword>
   *  </keywords>
   *
   * http://api.optitmobile.com/1/sendmessage/bulk.{format}
   *
   * @param array $array
   *   Array of all keywords, with a sub-array of all messages, with a sub-sub-array of keywords associated to these
   *   messages. It is a mess. @todo: Create a bulk messaging method which accepts Message objects and handles the rest.
   *
   * @return bool
   */
  public function messageBulkArray($array) {
    // Prepare XML document.
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><keywords/>');
    foreach ($array as $keywordId => $messages) {
      $keywordObj = $xml->addChild('keyword');
      $keywordObj->addAttribute('id', $keywordId);
      $messagesObj = $keywordObj->addChild('messages');
      foreach ($messages as $message) {
        $messageObj = $messagesObj->addChild('message');
        $messageObj->addAttribute('title', $message['title']);
        $messageObj->addAttribute('text', $message['message']);
        $recipientsObj = $messageObj->addChild('recipients');
        foreach ($message['phones'] as $phone) {
          $recipientsObj->addChild('phone', $phone);
        }
      }
    }

    // Prepare a request.
    $postParams = [];
    $postParams['data'] = $xml->asXML();
    $options = array('headers' => array('Content-Type' => 'text/xml'));

    // Talk to the API.
    if ($response = $this->http->post("sendmessage/bulk", NULL, $postParams, $options)) {
      return TRUE;
    }
    return FALSE;
  }


  public function messageBulkMMSArray($array) {
    // Prepare XML document.
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><keywords/>');
    foreach ($array as $keywordId => $messages) {
      $keywordObj = $xml->addChild('keyword');
      $keywordObj->addAttribute('id', $keywordId);
      $messagesObj = $keywordObj->addChild('messages');
      foreach ($messages as $message) {
        $messageObj = $messagesObj->addChild('message');
        $messageObj->addAttribute('title', $message['title']);
        $messageObj->addAttribute('text', $message['message']);
        if ($message['content_url']) {
          $messageObj->addAttribute('content_url', $message['content_url']);
        }
        $recipientsObj = $messageObj->addChild('recipients');
        foreach ($message['phones'] as $phone) {
          $recipientsObj->addChild('phone', $phone);
        }
      }
    }

    // Prepare a request.
    $postParams = [];
    $postParams['data'] = $xml->asXML();
    $options = array('headers' => array('Content-Type' => 'text/xml'));

    // Talk to the API.
    if ($response = $this->http->post("sendmessage/mms/bulk", NULL, $postParams, $options)) {
      return TRUE;
    }
    return FALSE;
  }


  public function setPage($page) {
    $this->page = $page;
    return $this;
  }

  /**
   * This method makes sure that once used, page gets reset to initial value - 1. So that next query does not get polluted
   * with previous query's pagination. Ugly, but efficient.
   */
  public function getPage() {
    $page = $this->page;
    $this->page = 1;
    return $page;
  }

  /**
   * A little bit ugly method and logic in general. It collects current page and total pages from the response and
   * populates temporary properties. This was the least invasive way of getting stats to Drupal's paginator.
   */
  private function collectStats($response) {
    $this->totalPages = $response['total_pages'];
    $this->currentPage = $response['current_page'];
  }
}
