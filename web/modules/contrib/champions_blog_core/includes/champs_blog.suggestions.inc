<?php

/**
 * Implements HOOK_theme_suggestions_HOOK_alter, adding template suggestions
 * for taxonomy terms so that I can get the blog to work.
 */
function champs_blog_theme_suggestions_taxonomy_term_alter(&$suggestions, $vars, $hook) {
  $term = $vars['elements']['#taxonomy_term'];
  $sanitized_view_mode = strtr($vars['elements']['#view_mode'], '.', '_');
  $suggestions[] = 'taxonomy_term__' . $term->bundle() . '__' . $sanitized_view_mode;
  $suggestions[] = 'taxonomy_term__' . $term->id() . '__' . $sanitized_view_mode;
  $suggestions[] = 'taxonomy_term__' . $sanitized_view_mode;

  return $suggestions;
}


/**
 * Implements hook_theme_suggestions_HOOK_alter().
 * This adds a theme suggestion based on the content type of the parent of a paragraph.
 * Hopefully this allows me to template all the blog paragraphs just like that.
 */
function champs_blog_theme_suggestions_paragraph_alter(&$suggestions, $variables) {
$paragraph = $variables['elements']['#paragraph'];
$parent = $paragraph->getParentEntity();
  if ($parent) {
    // NOTE: Looks like the ordering here decides the theme hook priority!
    // Lower ones have higher specificity, and therefore win over the others.
    $suggestions[] = 'paragraph__' . $parent->bundle() . '__child';
    $suggestions[] = 'paragraph__' . $parent->bundle() . '__' .  $paragraph->bundle();
  }

  return $suggestions;
}


/**
 *  LEARNT: Instead of messing about with duplicate templates, you should be
 * manipulating them through suggestions! MUHC EASIER!
 * Implements hook_theme_suggestions_HOOK().
 */
function champs_blog_theme_suggestions_views_view_alter(&$suggestions, $variables) {

  $view = $variables['view'];
  $suggestions[] = 'views_view__' . $view->id();
  $suggestions[] = 'views_view__' . $view->current_display;
  $suggestions[] = 'views_view__' . $view->id() . '__' . $view->current_display;

  return $suggestions;
}