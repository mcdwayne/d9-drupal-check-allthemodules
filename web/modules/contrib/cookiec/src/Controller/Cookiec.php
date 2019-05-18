<?php

namespace Drupal\cookiec\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Cookiec
 *
 * @package Drupal\cookiec\Controller
 */
class Cookiec extends ControllerBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Cookiec constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->config = $this->config('cookiec.settings');
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }


  /**
   * Render
   *
   * @return array
   */
  function renderPage(){
    $language = $this->languageManager->getCurrentLanguage()->getId();
    return array(
      '#title' => $this->config->get($language."_popup_title"),
      '#markup' => $this->config->get($language."_popup_p_private"),
    );
  }

}