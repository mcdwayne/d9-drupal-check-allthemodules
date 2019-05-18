<?php

namespace Drupal\oop_forms\Form;

use Drupal\oop_forms\Form\Element\EntityAutocomplete;
use Drupal\oop_forms\Form\Element\Item;
use Drupal\oop_forms\Form\Element\Link;
use Drupal\oop_forms\Form\Element\MachineName;
use Drupal\oop_forms\Form\Element\Number;
use Drupal\oop_forms\Form\Element\Select;
use Drupal\oop_forms\Form\Element\TextField;
use Drupal\oop_forms\Form\Element\Url;

class Builder {

  /**
   * @return \Drupal\oop_forms\Form\Element\TextField
   */
  public function createTextField() {
    return new TextField();
  }

  /**
   * @return \Drupal\oop_forms\Form\Element\Url
   */
  public function createUrl() {
    return new Url();
  }

  /**
   * @return \Drupal\oop_forms\Form\Element\Link
   */
  public function createLink() {
    return new Link();
  }

  /**
   * @return \Drupal\oop_forms\Form\Element\Item
   */
  public function createItem() {
    return new Item();
  }

  /**
   * @return \Drupal\oop_forms\Form\Element\EntityAutocomplete
   */
  public function createEntityAutocomplete() {
    return new EntityAutocomplete();
  }

  /**
   * @return \Drupal\oop_forms\Form\Element\Number
   */
  public function createNumber() {
    return new Number();
  }

  /**
   * @return \Drupal\oop_forms\Form\Element\MachineName
   */
  public function createMachineName() {
    return new MachineName();
  }

  public function createSelect() {
    return new Select();
  }
}
