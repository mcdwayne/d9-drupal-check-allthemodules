<?php

namespace Drupal\optit\Optit;

class Interest extends Entity {
  protected $url_interest_subscriptions;
  protected $name;
  protected $description;
  protected $created_at;
  protected $id;
  protected $mobile_subscription_count;
  protected $url;
  protected $status;


  public static function create(array $values, $skipValidation = FALSE) {
    $interest = new Interest();
    foreach ($values as $parameter => $value) {
      // No need to waste time on validation.
      if ($skipValidation) {
        $interest->set($parameter, $value);
      } // Set value upon successful validation.
      elseif ($interest->validate($parameter, $value)) {
        $interest->set($parameter, $value);
      } // Validation failed, throw exception.
      else {
        throw new \Exception('Invalid value for ' . $parameter);
      }
    }
    return $interest;
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
  public function getUrlInterestSubscriptions() {
    return $this->url_interest_subscriptions;
  }

  /**
   * @param mixed $url_interest_subscriptions
   */
  public function setUrlInterestSubscriptions($url_interest_subscriptions) {
    $this->url_interest_subscriptions = $url_interest_subscriptions;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param mixed $description
   */
  public function setDescription($description) {
    $this->description = $description;
  }


}


