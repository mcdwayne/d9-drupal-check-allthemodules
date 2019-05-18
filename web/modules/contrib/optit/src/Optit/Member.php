<?php

namespace Drupal\optit\Optit;

class Member extends Entity {
  protected $id;
  protected $phone;
  protected $first_name;
  protected $last_name;
  protected $address1;
  protected $address2;
  protected $city;
  protected $state;
  protected $zip;
  protected $gender;
  protected $birth_date;
  protected $email_address;
  protected $carrier_id;
  protected $carrier_name;
  protected $mobile_status;
  protected $created_at;
  protected $url;


  public static function create(array $values, $skipValidation = FALSE) {
    $entity = new Member();
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
  public function getFirstName() {
    return $this->first_name;
  }

  /**
   * @param mixed $first_name
   */
  public function setFirstName($first_name) {
    $this->first_name = $first_name;
  }

  /**
   * @return mixed
   */
  public function getLastName() {
    return $this->last_name;
  }

  /**
   * @param mixed $last_name
   */
  public function setLastName($last_name) {
    $this->last_name = $last_name;
  }

  /**
   * @return mixed
   */
  public function getAddress1() {
    return $this->address1;
  }

  /**
   * @param mixed $address1
   */
  public function setAddress1($address1) {
    $this->address1 = $address1;
  }

  /**
   * @return mixed
   */
  public function getAddress2() {
    return $this->address2;
  }

  /**
   * @param mixed $address2
   */
  public function setAddress2($address2) {
    $this->address2 = $address2;
  }

  /**
   * @return mixed
   */
  public function getCity() {
    return $this->city;
  }

  /**
   * @param mixed $city
   */
  public function setCity($city) {
    $this->city = $city;
  }

  /**
   * @return mixed
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @param mixed $state
   */
  public function setState($state) {
    $this->state = $state;
  }

  /**
   * @return mixed
   */
  public function getZip() {
    return $this->zip;
  }

  /**
   * @param mixed $zip
   */
  public function setZip($zip) {
    $this->zip = $zip;
  }

  /**
   * @return mixed
   */
  public function getGender() {
    return $this->gender;
  }

  /**
   * @param mixed $gender
   */
  public function setGender($gender) {
    $this->gender = $gender;
  }

  /**
   * @return mixed
   */
  public function getBirthDate() {
    return $this->birth_date;
  }

  /**
   * @param mixed $birth_date
   */
  public function setBirthDate($birth_date) {
    $this->birth_date = $birth_date;
  }

  /**
   * @return mixed
   */
  public function getEmailAddress() {
    return $this->email_address;
  }

  /**
   * @param mixed $email_address
   */
  public function setEmailAddress($email_address) {
    $this->email_address = $email_address;
  }

  /**
   * @return mixed
   */
  public function getCarrierId() {
    return $this->carrier_id;
  }

  /**
   * @param mixed $carrier_id
   */
  public function setCarrierId($carrier_id) {
    $this->carrier_id = $carrier_id;
  }

  /**
   * @return mixed
   */
  public function getCarrierName() {
    return $this->carrier_name;
  }

  /**
   * @param mixed $carrier_name
   */
  public function setCarrierName($carrier_name) {
    $this->carrier_name = $carrier_name;
  }

  /**
   * @return mixed
   */
  public function getMobileStatus() {
    return $this->mobile_status;
  }

  /**
   * @param mixed $mobile_status
   */
  public function setMobileStatus($mobile_status) {
    $this->mobile_status = $mobile_status;
  }
}


