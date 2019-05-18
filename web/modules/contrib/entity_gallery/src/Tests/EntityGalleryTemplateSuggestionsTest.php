<?php

namespace Drupal\entity_gallery\Tests;

/**
 * Tests entity gallery template suggestions.
 *
 * @group entity_gallery
 */
class EntityGalleryTemplateSuggestionsTest extends EntityGalleryTestBase {

  /**
   * Tests if template_preprocess_entity_gallery() generates the correct suggestions.
   */
  function testEntityGalleryThemeHookSuggestions() {
    // Create entity gallery to be rendered.
    $entity_gallery = $this->drupalCreateEntityGallery();
    $view_mode = 'full';

    // Simulate theming of the entity gallery.
    $build = \Drupal::entityManager()->getViewBuilder('entity_gallery')->view($entity_gallery, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_entity_gallery', array($variables));

    $this->assertEqual($suggestions, array('entity_gallery__full', 'entity_gallery__page', 'entity_gallery__page__full', 'entity_gallery__' . $entity_gallery->id(), 'entity_gallery__' . $entity_gallery->id() . '__full'), 'Found expected entity gallery suggestions.');

    // Change the view mode.
    $view_mode = 'entity_gallery.my_custom_view_mode';
    $build = \Drupal::entityManager()->getViewBuilder('entity_gallery')->view($entity_gallery, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_entity_gallery', array($variables));

    $this->assertEqual($suggestions, array('entity_gallery__entity_gallery_my_custom_view_mode', 'entity_gallery__page', 'entity_gallery__page__entity_gallery_my_custom_view_mode', 'entity_gallery__' . $entity_gallery->id(), 'entity_gallery__' . $entity_gallery->id() . '__entity_gallery_my_custom_view_mode'), 'Found expected entity gallery suggestions.');
  }

}
