<?php

namespace Drupal\contact_tools\Twig\Extension;

use Drupal\contact_tools\Service\ContactTools;

/**
 * Custom twig function for contact tools.
 */
class Extensions extends \Twig_Extension {

  /**
   * Contact tools service.
   *
   * @var \Drupal\contact_tools\Service\ContactTools
   */
  protected $contactTools;

  /**
   * Extensions constructor.
   */
  public function __construct(ContactTools $contact_tools) {
    $this->contactTools = $contact_tools;
  }

  /**
   * Returns the name of the extension.
   *
   * @return string
   *   Extension name.
   */
  public function getName() {
    return 'contact_tools';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $functions = [];

    $functions[] = new \Twig_SimpleFunction('contact_form', [$this, 'contactForm']);
    $functions[] = new \Twig_SimpleFunction('contact_form_ajax', [$this, 'contactFormAjax']);
    $functions[] = new \Twig_SimpleFunction('contact_modal', [$this, 'contactModal']);
    $functions[] = new \Twig_SimpleFunction('contact_modal_ajax', [$this, 'contactModalAjax']);

    return $functions;
  }

  /**
   * Return form render array with AJAX support.
   */
  public function contactFormAjax($contact_form_id = 'default_form', array $form_state_additions = []) {
    return $this->contactTools->getFormAjax($contact_form_id, $form_state_additions);
  }

  /**
   * Return form render array with AJAX support.
   */
  public function contactForm($contact_form_id = 'default_form', array $form_state_additions = []) {
    return $this->contactTools->getForm($contact_form_id, $form_state_additions);
  }

  /**
   * Return form render array with AJAX support.
   */
  public function contactModal($link_title, $contact_form, $link_options = [], $key = 'default') {
    return $this->contactTools->createModalLink($link_title, $contact_form, $link_options, $key);
  }

  /**
   * Return form render array with AJAX support.
   */
  public function contactModalAjax($link_title, $contact_form, $link_options = [], $key = 'default-ajax') {
    return $this->contactTools->createModalLinkAjax($link_title, $contact_form, $link_options, $key);
  }

}
