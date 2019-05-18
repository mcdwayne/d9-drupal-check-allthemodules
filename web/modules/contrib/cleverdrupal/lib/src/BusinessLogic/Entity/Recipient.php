<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 *
 */
class Recipient {
  /**
   * @var string
   */
  private $email;

  /**
   * @var bool*/
  private $isActive = FALSE;

  /**
   * @var \DateTime
   */
  private $activated = NULL;

  /**
   * @var \DateTime
   */
  private $registered = NULL;

  /**
   * @var \DateTime
   */
  private $deactivated = NULL;

  /**
   * @var string
   */
  private $source = '';

  /**
   * @var string
   */
  private $salutation = '';

  /**
   * @var string
   */
  private $title = '';

  /**
   * @var string
   */
  private $firstName = '';

  /**
   * @var string
   */
  private $lastName = '';

  /**
   * @var string
   */
  private $street = '';

  /**
   * @var string
   */
  private $zip = '';

  /**
   * @var string
   */
  private $city = '';

  /**
   * @var string
   */
  private $company = '';

  /**
   * @var string
   */
  private $state = '';

  /**
   * @var string
   */
  private $country = '';

  /**
   * @var \DateTime
   */
  private $birthday = NULL;

  /**
   * @var string
   */
  private $phone = '';

  /**
   * @var string
   */
  private $shop = '';

  /**
   * @var int
   */
  private $customerNumber = '';

  /**
   * @var string
   */
  private $language = '';

  /**
   * @var bool
   */
  private $newsletterSubscription = FALSE;

  /**
   * @var arrayAssociativearrayinformat[customAttributeNamecustomAttributeValue]
   */
  private $attributes = [];

  /**
   * @var \CleverReach\BusinessLogic\Entity\TagCollection
   */
  private $tags;

  /**
   * @var \CleverReach\BusinessLogic\Entity\OrderItem[]
   */
  private $orders = [];

  /**
   *
   */
  public function __construct($email) {
    $this->email = $email;
    $this->tags = new TagCollection();
  }

  /**
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * @param bool $isActive
   */
  public function setActive($isActive) {
    $this->isActive = (bool) $isActive;
  }

  /**
   *
   */
  public function isActive() {
    return $this->isActive;
  }

  /**
   * @return \DateTime
   */
  public function getActivated() {
    return !empty($this->activated) ? $this->activated : new \DateTime();
  }

  /**
   * @param \DateTime $activated
   */
  public function setActivated(\DateTime $activated = NULL) {
    $this->activated = $activated;
  }

  /**
   * @return \DateTime
   */
  public function getRegistered() {
    return $this->registered;
  }

  /**
   * @param \DateTime $registered
   */
  public function setRegistered(\DateTime $registered = NULL) {
    $this->registered = $registered;
  }

  /**
   * @return \DateTime
   */
  public function getDeactivated() {
    return !empty($this->deactivated) ? $this->deactivated : new \DateTime();
  }

  /**
   * @param \DateTime $deactivated
   */
  public function setDeactivated(\DateTime $deactivated = NULL) {
    $this->deactivated = $deactivated;
  }

  /**
   * @return string
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * @param string $source
   */
  public function setSource($source) {
    $this->source = $source;
  }

  /**
   * @return string
   */
  public function getSalutation() {
    return $this->salutation;
  }

  /**
   * @param string $salutation
   */
  public function setSalutation($salutation) {
    $this->salutation = $salutation;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * @param string $firstName
   */
  public function setFirstName($firstName) {
    $this->firstName = $firstName;
  }

  /**
   * @return string
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * @param string $lastName
   */
  public function setLastName($lastName) {
    $this->lastName = $lastName;
  }

  /**
   * @return string
   */
  public function getStreet() {
    return $this->street;
  }

  /**
   * @param string $street
   */
  public function setStreet($street) {
    $this->street = $street;
  }

  /**
   * @return string
   */
  public function getZip() {
    return $this->zip;
  }

  /**
   * @param string $zip
   */
  public function setZip($zip) {
    $this->zip = $zip;
  }

  /**
   * @return string
   */
  public function getCity() {
    return $this->city;
  }

  /**
   * @param string $city
   */
  public function setCity($city) {
    $this->city = $city;
  }

  /**
   * @return string
   */
  public function getCompany() {
    return $this->company;
  }

  /**
   * @param string $company
   */
  public function setCompany($company) {
    $this->company = $company;
  }

  /**
   * @return string
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @param string $state
   */
  public function setState($state) {
    $this->state = $state;
  }

  /**
   * @return string
   */
  public function getCountry() {
    return $this->country;
  }

  /**
   * @param string $country
   */
  public function setCountry($country) {
    $this->country = $country;
  }

  /**
   * @return \DateTime
   */
  public function getBirthday() {
    return $this->birthday;
  }

  /**
   * @param \DateTime $birthday
   */
  public function setBirthday(\DateTime $birthday = NULL) {
    $this->birthday = $birthday;
  }

  /**
   * @return string
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * @param string $phone
   */
  public function setPhone($phone) {
    $this->phone = $phone;
  }

  /**
   * @return string
   */
  public function getShop() {
    return $this->shop;
  }

  /**
   * @param string $shop
   */
  public function setShop($shop) {
    $this->shop = $shop;
  }

  /**
   * @return int
   */
  public function getCustomerNumber() {
    return $this->customerNumber;
  }

  /**
   * @param int $customerNumber
   */
  public function setCustomerNumber($customerNumber) {
    $this->customerNumber = $customerNumber;
  }

  /**
   * @return string
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * @param string $language
   */
  public function setLanguage($language) {
    $this->language = $language;
  }

  /**
   * @return bool
   */
  public function getNewsletterSubscription() {
    return $this->newsletterSubscription;
  }

  /**
   * @param bool $newsletterSubscription
   */
  public function setNewsletterSubscription($newsletterSubscription) {
    $this->newsletterSubscription = $newsletterSubscription;
  }

  /**
   * @return array
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * @param array $attributes
   */
  public function setAttributes(array $attributes) {
    $this->attributes = $attributes;
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tags
   */
  public function setTags($tags) {
    $this->tags = $tags;
  }

  /**
   * @return \CleverReach\BusinessLogic\Entity\OrderItem[]
   */
  public function getOrders() {
    return $this->orders;
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\OrderItem[] $orders
   */
  public function setOrders(array $orders) {
    $this->orders = $orders;
  }

}
