<?php
/**
 * @file
 * @author  Er. Sandeep Jangra
 * Contains \Drupal\example\Controller\ExampleController.
 * Please place this file under your example(module_root_folder)/src/Controller/
 */
namespace Drupal\newsletter_digest\Controller;
/**
 * Provides route responses for the Example module.
 */
class NewsletterDigestController {
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function newsletterDigest() {
    $element = array(
      '#markup' => 'Hello world!',
    );
    return $element;
  }

  public function newsletterGenral() {
    $element = array(
      '#markup' => 'Genral',
    );
    return $element;
  }
  public function newsletterCategory() {
    $element = array(
      '#markup' => 'Category',
    );
    return $element;
  }
  public function subscriberListing() {
    $element = array(
      '#markup' => 'Subscriber Listing',
    );
    return $element;
  }
  //public function sendMail() {
   // $element = array(
     // '#markup' => 'Send Mail',
   // );
  //  return $element;
 // }
  public function csvImport() {
    $element = array(
      '#markup' => 'CSV Import',
    );
    return $element;
  }
}
?>
