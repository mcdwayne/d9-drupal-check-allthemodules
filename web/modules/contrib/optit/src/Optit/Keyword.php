<?php

namespace Drupal\optit\Optit;

class Keyword extends Entity {
  protected $id;
  protected $internal_name;
  protected $keyword_name;
  protected $short_code;
  protected $status;
  protected $created_at;
  protected $keyword_type;
  protected $mobile_subscription_count;
  protected $url;
  protected $url_subscriptions;
  protected $url_interests;
  protected $billing_type;
  protected $interest_id;
  protected $welcome_msg_type;
  protected $welcome_msg;
  protected $web_form_verification_msg_type;
  protected $web_form_verification_msg;
  protected $already_subscribed_msg_type;
  protected $already_subscribed_msg;


  public static function create(array $values, $skipValidation = FALSE) {
    $keyword = new Keyword();
    foreach ($values as $parameter => $value) {
      // No need to waste time on validation.
      if ($skipValidation) {
        $keyword->set($parameter, $value);
      } // Set value upon successful validation.
      elseif ($keyword->validate($parameter, $value)) {
        $keyword->set($parameter, $value);
      } // Validation failed, throw exception.
      else {
        throw new \Exception('Invalid value for ' . $parameter);
      }
    }
    return $keyword;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @return mixed
   */
  public function getInternalName() {
    return $this->internal_name;
  }

  /**
   * @param mixed $internal_name
   */
  public function setInternalName($internal_name) {
    $this->internal_name = $internal_name;
  }

  /**
   * @return mixed
   */
  public function getKeywordName() {
    return $this->keyword_name;
  }

  /**
   * @param mixed $keyword_name
   */
  public function setKeywordName($keyword_name) {
    $this->keyword_name = $keyword_name;
  }

  /**
   * @return mixed
   */
  public function getShortCode() {
    return $this->short_code;
  }

  /**
   * @param mixed $short_code
   */
  public function setShortCode($short_code) {
    $this->short_code = $short_code;
  }

  /**
   * @return mixed
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param mixed $status
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return mixed
   */
  public function getCreatedAt() {
    return $this->created_at;
  }

  /**
   * @param mixed $created_at
   */
  public function setCreatedAt($created_at) {
    $this->created_at = $created_at;
  }

  /**
   * @return mixed
   */
  public function getKeywordType() {
    return $this->keyword_type;
  }

  /**
   * @param mixed $keyword_type
   */
  public function setKeywordType($keyword_type) {
    $this->keyword_type = $keyword_type;
  }

  /**
   * @return mixed
   */
  public function getMobileSubscriptionCount() {
    return $this->mobile_subscription_count;
  }

  /**
   * @param mixed $mobile_subscription_count
   */
  public function setMobileSubscriptionCount($mobile_subscription_count) {
    $this->mobile_subscription_count = $mobile_subscription_count;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param mixed $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  /**
   * @return mixed
   */
  public function getUrlSubscriptions() {
    return $this->url_subscriptions;
  }

  /**
   * @param mixed $url_subscriptions
   */
  public function setUrlSubscriptions($url_subscriptions) {
    $this->url_subscriptions = $url_subscriptions;
  }

  /**
   * @return mixed
   */
  public function getUrlInterests() {
    return $this->url_interests;
  }

  /**
   * @param mixed $url_interests
   */
  public function setUrlInterests($url_interests) {
    $this->url_interests = $url_interests;
  }

  /**
   * @return string
   */
  public function getBillingType() {
    return $this->billing_type;
  }

  /**
   * @param mixed $billing_type
   */
  public function setBillingType($billing_type) {
    $this->billing_type = strtolower($billing_type);
  }

  /**
   * @return mixed
   */
  public function allowedValuesBillingType() {
    return array(
      'unlimited' => 'Unlimited',
      'per-message' => 'Per message'
    );
  }

  /**
   * @return mixed
   */
  public function getInterestId() {
    return $this->interest_id;
  }

  /**
   * @param mixed $interest_id
   */
  public function setInterestId($interest_id) {
    $this->interest_id = $interest_id;
  }

  /**
   * @return mixed
   */
  public function getWelcomeMsgType() {
    return $this->welcome_msg_type;
  }

  /**
   * @param mixed $welcome_msg_type
   */
  public function setWelcomeMsgType($welcome_msg_type) {
    $this->welcome_msg_type = $welcome_msg_type;
  }

  public function allowedValuesWelcomeMsgType() {
    return array(
      'standard' => 'Standard',
      'semi-custom' => 'Semi-custom'
    );
  }

  /**
   * @return mixed
   */
  public function getWelcomeMsg() {
    return $this->welcome_msg;
  }

  /**
   * @param mixed $welcome_msg
   */
  public function setWelcomeMsg($welcome_msg) {
    $this->welcome_msg = $welcome_msg;
  }

  /**
   * @return mixed
   */
  public function getWebFormVerificationMsgType() {
    return $this->web_form_verification_msg_type;
  }

  public function allowedValuesWebFormVerificationMsgType() {
    return $this->allowedValuesWelcomeMsgType();
  }

  /**
   * @param mixed $web_form_verification_msg_type
   */
  public function setWebFormVerificationMsgType($web_form_verification_msg_type) {
    $this->web_form_verification_msg_type = $web_form_verification_msg_type;
  }

  /**
   * @return mixed
   */
  public function getWebFormVerificationMsg() {
    return $this->web_form_verification_msg;
  }

  /**
   * @param mixed $web_form_verification_msg
   */
  public function setWebFormVerificationMsg($web_form_verification_msg) {
    $this->web_form_verification_msg = $web_form_verification_msg;
  }

  /**
   * @return mixed
   */
  public function getAlreadySubscribedMsgType() {
    return $this->already_subscribed_msg_type;
  }

  public function allowedValuesAlreadySubscribedMsgType() {
    return array(
      'standard' => 'Standard',
      'custom' => 'Custom',
      'none' => 'None',
    );
  }

  /**
   * @param mixed $already_subscribed_msg_type
   */
  public function setAlreadySubscribedMsgType($already_subscribed_msg_type) {
    $this->already_subscribed_msg_type = $already_subscribed_msg_type;
  }

  /**
   * @return mixed
   */
  public function getAlreadySubscribedMsg() {
    return $this->already_subscribed_msg;
  }

  /**
   * @param mixed $already_subscribed_msg
   */
  public function setAlreadySubscribedMsg($already_subscribed_msg) {
    $this->already_subscribed_msg = $already_subscribed_msg;
  }


}


