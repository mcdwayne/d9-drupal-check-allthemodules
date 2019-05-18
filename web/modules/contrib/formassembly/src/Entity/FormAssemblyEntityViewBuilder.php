<?php

namespace Drupal\formassembly\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Drupal\formassembly\Component\Render\FormBodyUnescapedMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\formassembly\ApiMarkup;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Prepares the FormAssembly entity for display.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class FormAssemblyEntityViewBuilder extends EntityViewBuilder {

  /**
   * Formassembly markup service.
   *
   * @var \Drupal\formassembly\ApiMarkup
   */
  protected $markup;

  /**
   * Default logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  /**
   * Injected service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityManagerInterface $entity_manager,
    LanguageManagerInterface $language_manager,
    Registry $theme_registry = NULL,
    ApiMarkup $markup,
    LoggerInterface $loggerChannel,
    KillSwitch $killSwitch
  ) {
    parent::__construct($entity_type, $entity_manager, $language_manager,
      $theme_registry);
    $this->markup = $markup;
    $this->logger = $loggerChannel;
    $this->killSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('formassembly.markup'),
      $container->get('logger.channel.default'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(
    EntityInterface $entity,
    $view_mode = 'full',
    $langcode = NULL
  ) {
    // The interface does not allow type hinting to an entity type.
    if (!$entity instanceof FormAssemblyEntity) {
      return [];
    }
    if (empty($_GET['tfa_next'])) {
      $markup = $this->markup->getFormMarkup($entity);
    }
    else {
      $markup = $this->markup->getNextForm($_GET['tfa_next']);
    }
    list($attached, $bodyMarkup) = $this->splitMarkup($markup);
    $content['fa_markup'] = [
      '#type' => 'markup',
      '#markup' => new FormBodyUnescapedMarkup($bodyMarkup),
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => ['fa_form_attachments' => $attached],
    ];
    // No bubbling of max age:
    // @see https://www.drupal.org/project/drupal/issues/2352009.
    $this->killSwitch->trigger();
    return $content;
  }

  /**
   * Helper method to parse the markup into head and body.
   *
   * @param string $markup
   *   The markup returned from formassembly.
   *
   * @return array
   *   An array with 'head' and 'body' for expansion via list().
   */
  protected function splitMarkup($markup) {
    // Now, per FormAssembly support, we'll try to find a <div> element with
    // the class name "wFormContainer".
    $crawler = new Crawler();
    $crawler->addContent($markup);
    // Symfony's crawler will get the html inside the filtered tag.
    $innerBody = $crawler->filter('.wFormContainer')->html();
    // If we found the body wrapper div:
    if (!empty($innerBody)) {
      // Grab the raw html of the header.
      // Just in case there's another wrapper, we get the HTML before the first
      // <div>, which is probably the same <div> we just found.
      $headMarkup = substr($markup, 0, stripos($markup, '<div'));
      $headMarkup = trim($headMarkup);
      // Process and extract attachable items from this raw markup.
      $attached = $this->attachedHead($headMarkup);
      // Re-assemble the form wrapper filtered above.
      $bodyMarkup = "<div class=\"wFormContainer\">$innerBody</div>";
    }
    // If we didn't find the body wrapper div:
    else {
      // Fall back to using the entire markup in the body.
      $attached = [];
      $bodyMarkup = trim($markup);
    }
    return [$attached, $bodyMarkup];
  }

  /**
   * Helper method to take raw header from FormAssembly and prep for render.
   *
   * @param string $rawHeadMarkup
   *   The extracted head hmtl.
   *
   * @return array
   *   Array prepared for #attached.
   */
  protected function attachedHead($rawHeadMarkup) {
    $attached = [];
    $crawler = new Crawler();
    $crawler->addContent($rawHeadMarkup);
    libxml_use_internal_errors(TRUE);

    foreach ($crawler->filter('script, link[type="text/css"]') as $index => $node) {
      switch ($node->tagName) {

        case 'script':
          if ($node->hasAttribute('src')) {
            $attached[] = [
              '#type' => 'fa_form_external_js',
              '#src' => $node->getAttribute('src'),
              '#weight' => $index,
            ];
          }
          else {
            $attached[] = [
              '#type' => 'fa_form_inline_js',
              '#value' => $node->textContent,
              '#weight' => $index,

            ];
          }
          break;

        case 'link':
          $attached[] = [
            '#type' => 'fa_form_external_css',
            '#rel' => $node->getAttribute('rel'),
            '#href' => $node->getAttribute('href'),
            '#weight' => $index,
          ];
          break;

      }
    }
    return $attached;
  }

}
