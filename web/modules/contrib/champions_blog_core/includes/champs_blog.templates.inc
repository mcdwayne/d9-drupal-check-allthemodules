<?php

/**
 * Registers the templates used in the Champions Blog module.
 */


function champs_blog_theme($existing, $type, $theme, $path) {

  $templates_array = [];

  $nodes = [
    'cb-blog',
  ];

  $paragraphs = [
    'cb-element--cbsf-definition',
    'cb-element--cbsf-file-download',
    'cb-element--cbsf-overview',
    'cb-element--cbsf-image-caption',
    'cb-element--child',
    'cb-blog--child',
  ];

  $fields = [
    'default--node--field-cb-content--cb-blog',
    'default--node--cb-blog',
    'default--node--field-cb-related-posts--cb-blog',
    'paragraph--field-cb-heading',
    'paragraph--field-cb-text--cbsf-blockquote',
    'paragraph--field-cb-text--cbsf-image-caption',
    'paragraph--field-cb-image--cbsf-slider-gallery',
    'paragraph--field-cb-image--cbsf-two-images',
    'paragraph--field-cb-element--cb-element',
    'taxonomy-term--name--cb-tags',
  ];

  $views = [
    'view--cb-tax-tags',
    'view--cb-tax-topics',
    'view--cb-content-sections',
    'view--cb-popular-posts',
    'view--cb-author-profiles',
    'view-unformatted--cb-tax-topics',
    'view-unformatted--blog'
  ];


  $blog_views = [
    'cb-view-blog',
    'cb-view-topic',
    'cb-view-tag',
    'cb-view-author',
  ];

  $viewfields = [
    'node--field-filters--blog',
  ];

  $taxonomies = [
    'cb-blog-link',
    'cb-topic',
    'cb-tag',
    'name--cb-tags',
  ];


  foreach($nodes as $node) {
    $array = [];
    $array['template'] = $node;
    $array['base hook'] = 'node';
    $templates_array['node__' . champs_blog_dash_to_underscore($node)] = $array;
  };

  foreach($paragraphs as $paragraph) {
    $array = [];
    $array['template'] = $paragraph;
    $array['base hook'] = 'paragraph';
    $templates_array['paragraph__' . champs_blog_dash_to_underscore($paragraph)] = $array;
  };

  foreach($fields as $field) {
    $array = [];
    $array['template'] = $field;
    $array['base hook'] = 'field';
    $templates_array['field__' . champs_blog_dash_to_underscore($field)] = $array;
  };

  foreach($views as $view) {
    $array = [];
    $array['template'] = $view;
    $array['base hook'] = 'views-view';
    $templates_array['views_' . champs_blog_dash_to_underscore($view)] = $array;
  };

  foreach($blog_views as $blog_view) {
    $array = [];
    $array['template'] = 'view--cb-posts-listing';
    $array['base hook'] = 'views-view';
    $templates_array['views_view__' . champs_blog_dash_to_underscore($blog_view)] = $array;
  };

  foreach($blog_views as $blog_view) {
    $array = [];
    $array['template'] = 'views-view-fields--cb-view-row';
    $array['base hook'] = 'views-view-fields';
    $templates_array['views_view_fields__' . champs_blog_dash_to_underscore($blog_view)] = $array;
  };

  foreach($viewfields as $viewfield) {
    $array = [];
    $array['template'] = $viewfield;
    $array['base hook'] = 'viewfield';
    $templates_array['viewfield__' . champs_blog_dash_to_underscore($viewfield)] = $array;
  };

  foreach($taxonomies as $taxonomy) {
    $array = [];
    $array['template'] = $taxonomy;
    $array['base hook'] = 'taxonomy-term';
    $templates_array['taxonomy_term__' . champs_blog_dash_to_underscore($taxonomy)] = $array;
  };

  // Registering the input checkbox template
  $array = [];
  $array['template'] = 'input--checkbox';
  $array['base hook']  = 'input';
  $templates_array['input__checkbox'] = $array;

  return $templates_array;
}