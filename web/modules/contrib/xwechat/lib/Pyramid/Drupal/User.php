<?php

/**
 * @file
 *
 * 兼容D7/D8的User
 * D7一般只用于属性取值,所以这里只封装取值相关
 */

namespace Pyramid\Drupal;

class User {

    protected $user;

    public function __construct($user) {
        $this->user = $user;
    }

    public function id() {
        return $this->user->uid;
    }
    
    public function __get($key) {
        return isset($this->user[$key]) ? $this->user[$key] : null;
    }

    function __call($method, $param) {
        static $keys = array(
            'username' => 'name',
            'email'    => 'mail',
            'lastaccessedtime' => 'access',
        );
        $key = preg_replace('/^get/', '', strtolower($method));
        $key = isset($keys[$key]) ? $keys[$key] : $key;
        return isset($this->user[$key]) ? $this->user[$key] : null;
    }

    public static function compat() {
        global $user;
        if (class_exists('Drupal\Core\Session\UserSession')) {
            return $user;
        } else {
            return new static($user);
        }
    }

}
