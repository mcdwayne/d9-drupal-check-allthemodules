<?php

namespace Drupal\optit\Optit;

class Subscription extends Entity {
  protected $type;
  protected $phone;
  protected $member_id;
  protected $signup_date;
  protected $created_at;
  protected $url;
  protected $url_member;


  public static function create(array $values, $skipValidation = FALSE) {
    $entity = new Subscription();
    foreach ($values as $parameter => $value) {
      // No need to waste time on validation.
      if ($skipValidation) {
        $entity->set($parameter, $value);
      } // Set value upon successful validation.
      elseif ($entity->validate($parameter, $value)) {
        $entity->set($parameter, $value);
      } // Validation failed, throw exception.
      else {
        throw new \Exception('Invalid value for ' . $parameter);
      }
    }
    return $entity;
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
  public function getType() {
    return $this->type;
  }

  /**
   * @param mixed $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * @return mixed
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * @param mixed $phone
   */
  public function setPhone($phone) {
    $this->phone = $phone;
  }

  /**
   * @return mixed
   */
  public function getMemberId() {
    return $this->member_id;
  }

  /**
   * @param mixed $member_id
   */
  public function setMemberId($member_id) {
    $this->member_id = $member_id;
  }

  /**
   * @return mixed
   */
  public function getSignupDate() {
    return $this->signup_date;
  }

  /**
   * @param mixed $signup_date
   */
  public function setSignupDate($signup_date) {
    $this->signup_date = $signup_date;
  }

  /**
   * @return mixed
   */
  public function getUrlMember() {
    return $this->url_member;
  }

  /**
   * @param mixed $url_member
   */
  public function setUrlMember($url_member) {
    $this->url_member = $url_member;
  }




}


