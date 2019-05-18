<?php

namespace Drupal\minifier_html\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class MinifierHtmlSubscriber.
 *
 * @package Drupal\minifier_html
 */
class MinifierHtmlSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new MinifierHtmlSubscriber object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE] = ['minifilterHtmlResponse'];
    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is dispatched.
   *
   * @param \Event $event
   *   Event object have reponse full html String.
   */
  public function minifilterHtmlResponse(Event $event) {
    $event->getResponse()->setContent($this->minifierHtmlOutput($event->getResponse()->getContent()));
  }

  /**
   * Function return regular expression to remove whitespaces before tag.
   *
   * @return array
   *   Regular expression array to key value pair.
   */
  private function stripWhiteSpacesBeforeTag() {
    return ['/[^\S ]+\</s' => '<'];
  }

  /**
   * Function return regular expression to remove whitespaces after tag.
   *
   * @return array
   *   Regular expression array to key value pair.
   */
  private function stripWhiteSpacesAfterTag() {
    return ['/\>[^\S ]+/s' => '>'];
  }

  /**
   * Function return regular expression to remove whitespaces sequency.
   *
   * @return array
   *    Regular expression array to key value pair.
   */
  private function stripWhiteSpacesSequency() {
    return ['/(\s)+/s' => '\\1'];
  }

  /**
   * Function return regular expression to remove comments.
   *
   * @return array
   *    Regular expression array to key value pair.
   */
  private function stripHtmlComments() {
    return ['/<!--(.|\s)*?-->/' => ''];
  }

  /**
   * Function return regular expression to remove JS comments.
   *
   * Examples: \/* MultiLine Comment *\/ and // Single comment.
   *
   * @return array
   *    Regular expression array to key value pair.
   */
  private function stripJsComments() {
    // Strip C style comments.
    // Strip line comments (whole line only).
    return ['#/\*.*?\*/#s' => '', '#\n([ \t]*//.*?\n)*#s' => "\n"];
  }

  /**
   * Function return Minfied HTML output.
   *
   * @param string $html
   *    Page Output html.
   */
  private function stripStyleTagComments(&$html = NULL) {
    $find = $replace = [];
    preg_match_all('/<style[\s\S]*?>[\s\S]*?<\/style>/', $html, $matches);
    $filters = $this->stripJsComments();
    foreach ($matches as $scriptTags) {
      $find += $scriptTags;
      $replace += preg_replace(array_keys($filters), array_values($filters), $scriptTags);
    }
    $html = str_replace($find, $replace, $html);
  }

  /**
   * Function return Minfied HTML output.
   *
   * @param string $html
   *    Page Output html.
   */
  private function stripScriptTagComments(&$html = NULL) {
    $find = $replace = [];
    preg_match_all('/<script[\s\S]*?>[\s\S]*?<\/script>/', $html, $matches);
    $filters = $this->stripJsComments();
    foreach ($matches as $scriptTags) {
      $find += $scriptTags;
      $replace += preg_replace(array_keys($filters), array_values($filters), $scriptTags);
    }
    $html = str_replace($find, $replace, $html);
  }

  /**
   * Function return Minfied HTML output.
   *
   * @param string $html
   *    Page Output html.
   */
  private function stripInlineComments(&$html = NULL) {
    $this->stripScriptTagComments($html);
    $this->stripStyleTagComments($html);
  }

  /**
   * Function return Minfied HTML output.
   *
   * @param string $html
   *    Page Output html.
   *
   * @return string
   *   Minified html output.
   */
  private function compressHtmlOutput(&$html = NULL) {
    $filters = $this->stripWhiteSpacesBeforeTag() +
        $this->stripWhiteSpacesAfterTag() +
        $this->stripWhiteSpacesSequency();
    $html = preg_replace(array_keys($filters), array_values($filters), $html);
  }

  /**
   * Function return Minfied HTML output.
   *
   * @param string $html
   *    Page Output html.
   *
   * @return string
   *   Minified html output.
   */
  private function minifierHtmlOutput($html) {
    $this->stripInlineComments($html);
    $this->compressHtmlOutput($html);
    return $html;
  }

}
