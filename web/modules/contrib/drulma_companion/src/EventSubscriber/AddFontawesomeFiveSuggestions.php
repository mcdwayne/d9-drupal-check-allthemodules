<?php

namespace Drupal\drulma_companion\EventSubscriber;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Entity\File;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeSuggestionsAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add suggestions for Fontawesome 5.
 */
class AddFontawesomeFiveSuggestions implements EventSubscriberInterface {

  const TEMPLATE_SUFFIX = '__fa5';

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new AddFontawesomeFiveSuggestions instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The library discovery service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(
    LibraryDiscoveryInterface $libraryDiscovery,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->libraryDiscovery = $libraryDiscovery;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME_SUGGESTIONS_ALTER => 'addSuggestions',
    ];
  }

  /**
   * Respond to the event.
   */
  public function addSuggestions(ThemeSuggestionsAlterEvent $event) {
    if (
      in_array($event->getHook(), [
        'feed_icon',
        'input',
        'select',
        'file_link',
      ]) &&
      $this->isFontawesomeFiveEnabled()
    ) {
      $variables = $event->getVariables();
      $suggestions = $event->getSuggestions();

      foreach ($suggestions as $suggestion) {
        $suggestions[] = $suggestion . self::TEMPLATE_SUFFIX;
      }
      $suggestions[] = $event->getHook() . self::TEMPLATE_SUFFIX;

      $type = $variables['element']['#type'] ?? '';
      $type_suggestion = $event->getHook() . '__' . $type . self::TEMPLATE_SUFFIX;
      if ($type && !in_array($type_suggestion, $suggestions, TRUE)) {
        // Handle case likes type = date and actual type of the input = time.
        $subtype = $variables['element']['#attributes']['type'] ?? '';
        if ($subtype && $subtype != $type) {
          $type_suggestion = $event->getHook() . '__' . $type . '_' . $subtype . self::TEMPLATE_SUFFIX;
        }

        $suggestions[] = $type_suggestion;
      }
      $name = $variables['element']['#attributes']['name'] ?? '';
      if ($name) {
        // Remove any non-word character from the name.
        $name = preg_replace('~[\W]~', '', $name);
        $suggestions[] = $event->getHook() . '__' . $name . self::TEMPLATE_SUFFIX;
      }
      $formId = $variables['element']['#form_id'] ?? '';
      if ($formId && $type) {
        $suggestions[] = $event->getHook() . '__' . $type . '__' . $formId . self::TEMPLATE_SUFFIX;
      }

      // Add the value of the submit as a suggestion.
      if (
        $type === 'submit' &&
        $variables['element']['#value'] instanceof TranslatableMarkup
      ) {
        $untranslatedCleanString = strtolower(preg_replace('~[\W]~', '', $variables['element']['#value']->getUntranslatedString()));
        $suggestions[] = $variables['theme_hook_original'] . '__' . $untranslatedCleanString . self::TEMPLATE_SUFFIX;
      }

      if (
        isset($variables['file']) &&
        $variables['file'] instanceof File
      ) {
        $cleanMimeType = strtolower(preg_replace('~[\W]~', '', file_icon_class($variables['file']->getMimeType())));
        $suggestions[] = $event->getHook() . '__' . $cleanMimeType . self::TEMPLATE_SUFFIX;
      }

      $event->setSuggestions($suggestions);
    }
  }

  /**
   * Determine when the fontawesome library is loaded.
   *
   * NOTE: Remove the check for the module when the following issue
   * gets into the stable branch.
   * https://www.drupal.org/project/drupal/issues/2347783
   * and use the exception.
   */
  protected function isFontawesomeFiveEnabled() {
    if ($this->moduleHandler->moduleExists('lp_fontawesome')) {
      foreach (['fontawesome', 'fontawesomesvg'] as $libraryName) {
        $library = $this->libraryDiscovery->getLibraryByName('lp_fontawesome', $libraryName);
        if (!empty($library['libraries_provider']['enabled']) &&
          version_compare($library['version'], '5.0.0') >= 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
