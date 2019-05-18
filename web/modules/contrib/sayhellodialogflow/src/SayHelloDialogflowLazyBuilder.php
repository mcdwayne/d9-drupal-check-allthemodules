<?php

namespace Drupal\say_hello_dialogflow;

/**
 * Lazy builder for the Say Hello Dialogflow.
 */
class SayHelloDialogflowLazyBuilder {

  /**
   * @var \Drupal\say_hello_dialogflow\SayHelloDialogflow
   */
  protected $dialogflow_menu;

  /**
   * HelloWorldLazyBuilder constructor.
   *
   * @param \Drupal\say_hello_dialogflow\SayHelloDialogflow $dialogflow_menu
   */
  public function __construct(SayHelloDialogflow $dialogflow_menu) {
    $this->dialogflow_menu = $dialogflow_menu;
  }

  /**
   * Renders the Dialogflow Menu.
   */
  public function renderDialogflow() {
    return $this->dialogflow_menu->getDialogflowComponent();
  }
}