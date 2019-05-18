<?php

namespace Drupal\contest;

use Drupal\user\Entity\User;

/**
 * An easy to access contest user.
 */
class ContestUser {
  public $uid;
  public $name;
  public $mail;
  public $status;
  public $fullName;
  public $business;
  public $address;
  public $city;
  public $state;
  public $zip;
  public $phone;
  public $birthdate;
  public $optin;
  public $lang;
  public $pass;
  public $title;
  public $url;
  protected $fields = [
    'name'      => 'fullName',
    'business'  => 'business',
    'address'   => 'address',
    'city'      => 'city',
    'state'     => 'state',
    'zip'       => 'zip',
    'birthdate' => 'birthdate',
    'phone'     => 'phone',
    'optin'     => 'optin',
  ];

  /**
   * Just make sure there are some defaults that won't throw an error.
   *
   * @param int $uid
   *   The user's ID.
   * @param array $xtra
   *   Any optional fields.
   */
  public function __construct($uid, array $xtra = []) {
    if (!is_numeric($uid)) {
      return NULL;
    }
    $usr = User::load($uid);

    if (!isset($usr->uid->value)) {
      return NULL;
    }
    $this->uid = $usr->uid->value;
    $this->name = $usr->name->value;
    $this->mail = $usr->mail->value;
    $this->lang = $usr->getPreferredLangcode();
    $this->status = $usr->status->value;

    foreach ($this->fields as $field => $property) {
      $this->{$property} = $this->get($field);
    }
    $this->pass = !empty($xtra['pass']) ? $xtra['pass'] : '';
    $this->title = !empty($xtra['title']) ? $xtra['title'] : '';
    $this->url = !empty($xtra['url']) ? $xtra['url'] : '';
  }

  /**
   * Magic get.
   */
  public function __get($property) {
    $property = ($property == 'full_name') ? 'fullName' : $property;

    return property_exists($this, $property) ? $this->{$property} : NULL;
  }

  /**
   * Magic set.
   */
  public function __set($property, $value) {
    $property = ($property == 'name' || $property == 'full_name') ? 'fullName' : $property;

    if (!property_exists($this, $property)) {
      return NULL;
    }
    $this->{$property} = $value;

    $this->set($property, $value);

    return $this->{$property};
  }

  /**
   * Determine if the profile is complete.
   *
   * @param string $role
   *   The role to test.
   *
   * @return bool
   *   True if the user has a completed profile.
   */
  public function completeProfile($role = 'entrant') {
    $status = (bool) ($this->fullName && $this->address && $this->city && $this->state && $this->zip && $this->phone);

    switch ($role) {
      case '':
      case 'entrant':
        return $status;

      case 'host':
      case 'sponsor':
        return (bool) ($status && $this->business);
    }
    return FALSE;
  }

  /**
   * Create a new Drupal user and return it's ContestUser object.
   *
   * @param string $name
   *   The username.
   * @param string $mail
   *   The user email.
   * @param array $xtra
   *   Extra properties.
   */
  public static function create($name, $mail, array $xtra = []) {
    $args = [
      'name'     => self::usrNameGenerator($name),
      'mail'     => $mail,
      'pass'     => self::usrPasswordGenerator($mail),
      'roles'    => [2 => 'authenticated user'],
      'status'   => 1,
      'init'     => $mail,
      'timezone' => \Drupal::config('system.date')->get('timezone.default'),
    ];
    $usr = User::create($args);

    $usr->save();

    return new static($usr->uid->value, array_merge($xtra, ['pass' => $args['pass']]));
  }

  /**
   * Get the Drupal user account.
   *
   * @return \Drupal\user\Entity\User
   *   The Drupal user account object.
   */
  public function getAccount() {
    return $this->uid ? User::load($this->uid) : User::load(0);
  }

  /**
   * Load a ContestUser by email.
   *
   * @param string $mail
   *   The user email.
   * @param array $xtra
   *   Extra properties.
   */
  public static function loadByMail($mail, array $xtra = []) {
    $usrs = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $mail]);

    if (count($usrs) > 1) {
      return NULL;
    }
    $usr = array_shift($usrs);

    return !empty($usr->uid->value) ? new static($usr->uid->value, $xtra) : new static(0, $xtra);
  }

  /**
   * Save the fields to the Drupal user object.
   */
  public function save() {
    foreach ($this->fields as $field => $property) {
      $this->set($field, $this->{$property});
    }
  }

  /**
   * Convert the provided string to a lowercase stroke delimited string.
   *
   * @param string $txt
   *   The string to convert.
   *
   * @return string
   *   A lowercase stroke delimited string.
   */
  protected static function format($txt) {
    return preg_replace(['/[^a-z0-9]+/', '/^-+|-+$/'], ['-', ''], strtolower($txt));
  }

  /**
   * Extract the contest profile value from the user object.
   *
   * @param string $field
   *   The field name.
   * @param string $default
   *   The default value.
   *
   * @return string
   *   The safe_value if set.
   */
  protected function get($field = '', $default = '') {
    $field = $this->trans($field);
    $usr = User::load($this->uid);

    return isset($usr->{$field}->value) ? $usr->{$field}->value : $default;
  }

  /**
   * Set a plain text Drupal user field.
   *
   * @param string $field
   *   The field name.
   * @param int|string $value
   *   The field's value.
   */
  protected function set($field = '', $value = '') {
    if (!$this->uid) {
      return;
    }
    $usr = User::load($this->uid);
    $usr->set($this->trans($field), $value);
    $usr->save();
  }

  /**
   * Translate the name field.
   *
   * @param string $field
   *   The field name.
   *
   * @return string
   *   The translated field name.
   */
  protected function trans($field = '') {
    return ($field == 'fullName' || $field == 'full_name') ? 'field_contest_name' : "field_contest_$field";
  }

  /**
   * Generate a unique username.
   *
   * @param string $name
   *   The user's name.
   *
   * @return string
   *   A unique username.
   */
  protected static function usrNameGenerator($name = '') {
    $min = 10;
    $max = 99;
    $username = $name ? self::format($name) : strtolower(substr(md5(REQUEST_TIME), 0, $min));

    for ($i = $min; $i <= $max; $i++) {
      $found = ContestStorage::usrNameExists($username);

      if (!$found) {
        return $username;
      }
      $username = preg_replace('/\W/', '', strtolower($name)) . '-' . rand($min, $max);
    }
    return self::usrNameGenerator($username);
  }

  /**
   * Generate a password from an email address.
   *
   * @param string $email
   *   The user's email address.
   *
   * @return string
   *   A password that hopefully isn't too terrible.
   */
  protected static function usrPasswordGenerator($email = '') {
    return $email ? preg_replace('/@.*/', '', $email) . '-' . strtolower(user_password(rand(4, 6))) : strtolower(user_password(rand(8, 12)));
  }

}
