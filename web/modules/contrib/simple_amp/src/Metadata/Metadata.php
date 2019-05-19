<?php

namespace Drupal\simple_amp\Metadata;

use Drupal\simple_amp\Metadata\Base;

/**
 * Generate AMP metadata.
 */
class Metadata extends Base {

  protected $type = 'NewsArticle';
  protected $mainEntityOfPage;
  protected $headline;
  protected $datePublished;
  protected $dateModified;
  protected $description;
  protected $author;
  protected $publisher;
  protected $image;

  public function setContext($value) {
    $this->context = $value;
    return $this;
  }

  public function getContext() {
    return !empty($this->context) ? $this->context : 'http://schema.org';
  }

  public function setMainEntityOfPage($url) {
    $this->mainEntityOfPage = $url;
    return $this;
  }

  public function getMainEntityOfPage() {
    return $this->mainEntityOfPage;
  }

  public function setHeadline($value) {
    $this->headline = $value;
    return $this;
  }

  public function getHeadline() {
    return $this->headline;
  }

  public function setDatePublished($timestamp) {
    $this->datePublished = $timestamp;
    return $this;
  }

  public function getDatePublished() {
    return date('c', $this->datePublished);
  }

  public function setDateModified($timestamp) {
    $this->dateModified = $timestamp;
    return $this;
  }

  public function getDateModified() {
    return date('c', $this->dateModified);
  }

  public function setDescription($value) {
    $this->description = $value;
    return $this;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setAuthor($a) {
    if (is_array($a)) {
      foreach ($a as $author) {
        if (is_a($author, '\Drupal\simple_amp\Metadata\Author')) {
          $this->author[] = $author->build();
        }
      }
    }
    else {
      if (is_a($a, '\Drupal\simple_amp\Metadata\Author')) {
        $this->author = $a->build();
      }
    }
    return $this;
  }

  public function getAuthor() {
    return $this->author;
  }

  public function setPublisher($p) {
    if (is_array($p)) {
      foreach ($p as $publisher) {
        if (is_a($publisher, '\Drupal\simple_amp\Metadata\Publisher')) {
          $this->publisher[] = $publisher->build();
        }
      }
    }
    else {
      if (is_a($p, '\Drupal\simple_amp\Metadata\Publisher')) {
        $this->publisher = $p->build();
      }
    }
    return $this;
  }

  public function getPublisher() {
    return $this->publisher;
  }

  public function setImage(Image $image) {
    $this->image = $image;
    return $this;
  }

  public function getImage() {
    return is_a($this->image, '\Drupal\simple_amp\Metadata\Image') ? $this->image->build() : '';
  }

  public function build() {
    $params = [];
    if ($context = $this->getContext()) {
      $params['@context'] = $context;
    }
    if ($type = $this->getType()) {
      $params['@type'] = $type;
    }
    if ($mainEntityOfPage = $this->getMainEntityOfPage()) {
      $params['mainEntityOfPage'] = $mainEntityOfPage;
    }
    if ($headline = $this->getHeadline()) {
      $params['headline'] = $headline;
    }
    if ($datePublished = $this->getDatePublished()) {
      $params['datePublished'] = $datePublished;
    }
    if ($dateModified = $this->getDateModified()) {
      $params['dateModified'] = $dateModified;
    }
    if ($description = $this->getDescription()) {
      $params['description'] = $description;
    }
    if ($author = $this->getAuthor()) {
      $params['author'] = $author;
    }
    if ($publisher = $this->getPublisher()) {
      $params['publisher'] = $publisher;
    }
    if ($image = $this->getImage()) {
      $params['image'] = $image;
    }
    return $params;
  }

}
