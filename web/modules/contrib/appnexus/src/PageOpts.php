<?php

namespace Drupal\appnexus;

/**
 * Generate parameters for apntag.setPageOpts() method.
 */
class PageOpts {

  protected $member;
  protected $quotes = TRUE;
  protected $keywords = [];
  protected $characters = [
    '"', "'", '=', '!', '+', '#', '*', '~', ';', '^', '(', ')', '<', '>', '[', ']', ',', '&', '@', ':', '?', '%', '/'
  ];

  public function setMember($id) {
    $this->member = $id;
    return $this;
  }

  public function getMember() {
    return (int) $this->member;
  }

  public function setKeywordQuotes($quotes = TRUE) {
    $this->quotes = $quotes;
    return $this;
  }

  public function getKeywordQuotes() {
    return (bool) $this->quotes;
  }

  public function setKeyword($key, $value) {
    $safe_value = $this->cleanup($value);
    $safe_key = $this->cleanup($key);
    if ($this->getKeywordQuotes()) {
      $this->keywords["'" . $safe_key . "'"] = $safe_value;
    }
    else {
      $this->keywords[$safe_key] = $safe_value;
    }
    return $this;
  }

  public function getKeywords() {
    return (array) $this->keywords;
  }

  public function build() {
    $opts = [];
    if ($member = $this->getMember()) {
      $opts['member'] = $member;
    }
    if ($keywords = $this->getKeywords()) {
      $opts['keywords'] = $keywords;
    }
    return $opts;
  }

  protected function cleanup($value) {
    return str_replace($this->characters, '', $value);
  }

}
