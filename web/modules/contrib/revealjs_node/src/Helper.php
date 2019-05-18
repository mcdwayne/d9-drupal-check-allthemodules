<?php

namespace Drupal\revealjs_node;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Helper.
 *
 * @package Drupal\revealjs_node
 */
class Helper implements ContainerInjectionInterface {

  const BUNDLE = 'reveal_js_presentation';

  /**
   * The FE theme, extracted from the entity.
   *
   * @var string
   */
  private $theme = 'beige';

  /**
   * Revealjs setting - from configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $settings;

  /**
   * Constructor.
   *
   * @inheritdoc
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->settings = $configFactory->get('revealjs.settings');
  }

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory')
    );
  }

  /**
   * Checks wether the entity is a presentation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The presentation entity (node or media).
   */
  public function isPresentation(EntityInterface $entity) {
    $isPresentation = FALSE;
    if ($entity->bundle() == self::BUNDLE) {
      $isPresentation = TRUE;
    }
    return $isPresentation;
  }

  /**
   * Getter for settings.
   *
   * @return array
   *   the settings
   */
  public function getSettings() {
    return $this->settings->get();
  }

  /**
   * Setter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The presentation entity (node or media).
   */
  public function setPresentationTheme(EntityInterface $entity) {
    try {
      /** @var \Drupal\Core\Field\FieldItemList $theme */
      $theme = $entity->get('presentation_theme');
      if ($theme) {
        $this->theme = $theme->getString();
      }
    }
    catch (\Exception $ex) {
      // The field presentation_theme does not exist
      // We use the hardcoded default theme.
    }
  }

  /**
   * Getter for the presentation theme.
   *
   * @return string
   *   the theme name
   */
  public function getPresentationTheme() {
    return 'revealjs/' . $this->theme;
  }

}
