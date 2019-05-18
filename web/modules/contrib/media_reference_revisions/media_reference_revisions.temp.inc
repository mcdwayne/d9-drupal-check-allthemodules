<?php

if ($entity->bundle() == 'page') {
  // LifeCenter Updates & Additions Block.
  if ($display->getComponent('lc_additions_updates')) {

    $view = Views::getView('lifecenter_updates_additions');

    if ($view) {
      $view->setDisplay('latest_updates_3_up');
      // This extra field is only available on LifeCenter landing page.
      // Set the content type to filter on to lifecenter items.
      $view->setArguments(array('lifecenter_item'));
      $result = $view->render('latest_updates_3_up');
      $build['lc_additions_updates'] = $result;
    }
  }
  // LifeCenter Search Block.
  if ($display->getComponent('lc_search')) {
    $form = \Drupal::formBuilder()->getForm('Drupal\abilitylab_lifecenter\Form\LifeCenterSearchForm');
    if ($form) {
      $build['lc_search'] = $form;
    }
  }

  // Rmd Search Block.
  if ($display->getComponent('rmd_search')) {
    $form = \Drupal::formBuilder()->getForm('Drupal\abilitylab_contextual_content\Form\RmdSearchForm');
    if ($form) {
      $build['rmd_search'] = $form;
    }
  }
  // RMD Upload Form Block.
  if ($display->getComponent('rmd_upload_form_block')) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance('rmd_upload_block', $config);
    $render = $plugin_block->build();
    $build['rmd_upload_form_block'] = $render;
  }
}

// Adds cta render element for these bundles and view mode using the cta-block-three-buttons.
// This content is implemented in multiple places.
// @see abilitylab_contextual_content_entity_view() where it's added to
// center, condition and department full view mode render arrays.
// @see InterestedCTABlock where it's available from a custom block config.
// @see ContactUsBreakerBlock where it's available from a custom block.
if (in_array($entity->bundle(), ['center', 'condition', 'department']) && $view_mode == 'full') {
  $build['cta'] = [
    '#theme' => 'cta_block_three_buttons',
    '#title' => t('Get Connected.'),
    '#subtitle' => '1-844-355-ABLE',
    '#button_1_label' => t('Request an Appointment'),
    '#button_1_url' => '/request-appointment',
    '#button_2_label' => t('Refer a Patient'),
    '#button_2_url' => '/refer-patient',
    '#button_3_label' => t('Visit the Patient Portal'),
    '#button_3_url' => 'https://ric.iqhealth.com/home',
  ];
}

if ($entity->getEntityTypeId() == 'taxonomy_term' && $view_mode == 'term_with_tool_tip' && !empty($build['description'][0]['#text'])) {
  $build['tool_tip'] = ['#markup' => strip_tags($build['description'][0]['#text'])];
}

if ($entity->bundle() == 'condition' && $view_mode == 'full') {
  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [
    'title' => t('Latest Updates'),
  ];
  $plugin_block = $block_manager->createInstance('latest_news_block', $config);
  $render = $plugin_block->build();
  $build['latest_news_block'] = $render;

  if (!empty($build['field_associated_clinicians'][0]) && $entity->field_primary_condition->isEmpty() === FALSE) {
    $tid = $entity->field_primary_condition->getValue()[0]['target_id'];
    $build['view_all_clinicians_link'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => t('View All'),
      '#attributes' => [
        'class' => ['cta-button'],
        'href' => '/search?profile_type=clinician&condition=' . $tid,
      ],
    ];
  }

}

if ($entity->getEntityTypeId() == 'field_collection_item' && !empty($build['field_learn_more_link'])) {
  if (!empty($build['field_learn_more_link'][0]['#url'])) {
    $build['link'] = ['#markup' => $build['field_learn_more_link'][0]['#url']->toString()];
  }
}

if ($entity->bundle() == 'field_unordered_list' && $view_mode == 'full') {
  if (!empty($build['field_card_title'][0]['#context']['value'])) {
    $identifier = HTML::cleanCssIdentifier(strtolower($build['field_card_title'][0]['#context']['value']));
    $build['section_class'] = ['#markup' => 'profile-section--' . $identifier];
  }
}

if ($entity->bundle() == 'article' && $view_mode == 'full') {
  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [
    'title' => t('More Like This'),
  ];

  $plugin_block = $block_manager->createInstance('latest_news_block', $config);
  $render = $plugin_block->build();
  $build['more_like_this'] = $render;
}

$three_item_latest_updates_types = [
  'event',
  'lifecenter_item',
  'location',
];

if (in_array($entity->bundle(), $three_item_latest_updates_types) && $view_mode == 'full') {
  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [
    'title' => t('Latest Updates'),
  ];

  $plugin_block = $block_manager->createInstance('latest_news_three_item', $config);
  $render = $plugin_block->build();
  $build['latest_news_block'] = $render;
}

if ($entity->bundle() == 'rehabilitation_measure_instrumen' && $view_mode == 'search_result' && !empty($build['field_time_to_administer'][0])) {
  $build['field_time_to_administer'][0]['#view_mode'] = 'search_result';
}

if ($entity->bundle() == 'article' && $view_mode == 'full' && $entity->field_page_type[0]->getValue()['value'] == 'patient_story') {
  $config = [];
  $plugin_block = $block_manager->createInstance('about_our_care_block', $config);
  $render = $plugin_block->build();
  $build['about_our_care'] = $render;
}

if ($entity->bundle() == 'clinical_trial' && $view_mode == 'full') {
  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [
    'title' => t('More Studies Like This'),
  ];
  $plugin_block = $block_manager->createInstance('more_like_this', $config);
  $render = $plugin_block->build();
  $build['more_like_this'] = $render;

  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [
    'title' => t('Latest Updates'),
    'bundles' => ['article'],
  ];

  $plugin_block = $block_manager->createInstance('latest_news_three_item', $config);
  $render = $plugin_block->build();
  $build['latest_news_block'] = $render;
}

if ($entity->bundle() == 'rehabilitation_measure_instrumen' && $view_mode == 'full') {
  $block_manager = \Drupal::service('plugin.manager.block');
  $config = [
    'title' => t('More Like This'),
    'description' => t('We have reviewed nearly 300 instruments for use with a number of diagnoses including stroke, spinal cord injury and traumatic brain injury among several others.'),
    'bundles' => ['rehabilitation_measure_instrumen'],
  ];
  $plugin_block = $block_manager->createInstance('more_like_this_rehab_instrument', $config);
  $render = $plugin_block->build();
  $build['more_like_this'] = $render;
}


if (in_array($entity->getEntityTypeId(), ['node', 'group', 'profile'])) {
  $bundle_display = \Drupal::service('bundle_display')->getBundleDisplay($entity);
  if (!empty($bundle_display)) {
    $build['bundle_display'] = ['#markup' => $bundle_display];
  }
}

if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'center' && $view_mode == 'homepage_round_icon') {
  if ($text = $build['field_teaser_title'][0]['#context']['value']) {
    $text = str_replace(' Innovation Center', '', $text);
    $build['field_teaser_title'][0]['#context']['value'] = $text;
  }
}

// Replaces large_date view mode with card view mode
if ($entity->getEntityTypeId() == 'node'
  && in_array($entity->bundle(), ['event', 'lifecenter_event', 'lifecenter_group'])
  && $view_mode == 'card') {
  // Only include the organization name if it has been set.
  if (isset($build['content']['field_address'][0]['organization'])) {
    $build['location_name'] = ['#markup' => $build['content']['field_address'][0]['organization']];
  }

  // Turn event start date into day of the month.
  $start_timestamp = "";
  $end_timestamp = "";
  if (!empty($entity->field_event_start_date) && !$entity->field_event_start_date->isEmpty()) {
    $start_timestamp = strtotime($entity->field_event_start_date->getValue()[0]['value']);
    $dateFormatter = \Drupal::service('date.formatter');
    $build['start_date_day_only'] = ['#markup' => $dateFormatter->format($start_timestamp, 'day_only')];
    $build['start_date'] = ['#markup' => $dateFormatter->format($start_timestamp, 'twelve_hour_minutes_am_pm')];
    $build['start_date_short_month_full_year'] = ['#markup' => $dateFormatter->format($start_timestamp, 'short_month_full_year')];
  }
  if (!empty($entity->field_event_end_date) && !$entity->field_event_end_date->isEmpty()) {
    $end_timestamp = strtotime($entity->field_event_end_date->getValue()[0]['value']);
    $build['end_date'] = ['#markup' => $dateFormatter->format($end_timestamp, 'twelve_hour_minutes_am_pm')];
  }
}

if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'location' && $view_mode == 'search_result') {

  $settings = [
    'type' => 'google_directions_formatter',
    'settings' => $build['field_address']['#items']->getSettings(),
  ];
  $build['directions_link'] = $build['field_address']['#items']->view($settings);

  $build['clinician_search_link'] = [['#markup' => '/search?profile_type=clinician&location_id=' . $entity->id()]];

}


if ($entity->getEntityTypeId() == 'field_collection_item' && $entity->bundle() == 'field_content') {
  if (!empty($build['field_override_title'][0]) && !empty($build['field_referenced_node'][0]['#node']->field_teaser_title)) {
    $build['field_referenced_node'][0]['#node']->field_teaser_title->setValue($build['field_override_title'][0]['#context']['value']);
    $build['field_referenced_node'][0]['#cache']['keys'][] = 'overridden_title';
    $build['field_referenced_node'][0]['#cache']['keys'][] = $build['field_override_title']['#object']->id();
    unset($build['field_override_title'][0]);
  }
}

if ($entity->getEntityTypeId() == 'field_collection_item' && $entity->bundle() == 'field_service_contacts') {
  if (empty($build['field_image'][0]) && !empty($build['field_profile_reference'][0]['field_profile_image'][0])) {
    $build['field_image'][0] = $build['field_profile_reference'][0]['field_profile_image'][0];
  }

  if (empty($build['field_contact_full_name'][0]) && !empty($build['field_profile_reference'][0]['field_display_name'][0])) {
    $build['field_contact_full_name'][0] = $build['field_profile_reference'][0]['field_display_name'][0];
  }

  if (empty($build['field_phone_number'][0]) && !empty($build['field_profile_reference'][0]['field_telephone_number'][0])) {
    $build['field_phone_number'][0] = $build['field_profile_reference'][0]['field_telephone_number'][0];
  }

}

if ($entity->getEntityTypeId() == 'field_collection_item' && $entity->bundle() == 'field_hero_tabs') {
  HeroTabsViewBuilder::alterBuild($build, $entity, $display, $view_mode);
}

if ($entity->getEntityTypeId() == 'node' && !in_array($view_mode, ['full', 'default'])) {
  TeaserViewBuilder::alterBuild($build, $entity, $display, $view_mode);
}
elseif ($entity->getEntityTypeId() == 'node' && in_array($view_mode, ['full', 'default'])) {
  FullViewBuilder::alterBuild($build, $entity, $display, $view_mode);
}

if ($entity->getEntityTypeId() == 'shopify_product') {
//    dump($build);exit;
//    FullViewBuilder::alterBuild($build, $entity, $display, $view_mode);
}

if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'job' && $view_mode == 'teaser') {
  if (!empty($build['field_job_id'][0]['#template'])) {
    $build['field_job_id'][0]['#template'] = '{{ value|trim }}';
  }
  if (!empty($build['field_job_city'][0]['#template'])) {
    $build['field_job_city'][0]['#template'] = '{{ value|trim }}';
  }
  if (!empty($build['field_job_state'][0]['#template'])) {
    $build['field_job_state'][0]['#template'] = '{{ value|trim }}';
  }
  if (!empty($build['field_job_country'][0]['#template'])) {
    $build['field_job_country'][0]['#template'] = '{{ value|trim }}';
  }
  if (!empty($build['field_apply_url'][0]['#context']['value'])) {
    $build['field_apply_url'] = ['#markup' => $build['field_apply_url'][0]['#context']['value']];
  }
}

// Add easy access link for 1_up paragraph image.
if ($entity->getEntityTypeId() == 'paragraph' && in_array($entity->bundle(), ['1_up', 'new_ric'])) {
  if (!empty($build['field_cta_button_link'][0]['#url'])) {
    $build['link'] = ['#markup' => $build['field_cta_button_link'][0]['#url']->toString()];
  }
}

if ($entity->getEntityTypeId() == 'paragraph' && $entity->bundle() == 'pull_quote') {
  $parent = $build['#paragraph']->getParentEntity();

  if (empty($parent)) {
    $parent = \Drupal::routeMatch()->getParameter('node');
  }

  if (!empty($parent)) {
    $social = \Drupal::service('abilitylab_social.buttons')
      ->build($parent->getEntityTypeId(), $parent->id(), 'social_collapsed');
    $build['field_social'] = $social;
  }
  else {
    global $base_root;
    $current_uri = \Drupal::request()->getRequestUri();
    $current_path = $base_root . "/" . $current_uri;
    $social = \Drupal::service('abilitylab_social.buttons')
      ->buildFromUrl($current_path, 'Abilitylabs', 'social_collapsed');
  }
}

// Add easy access link for 1_up paragraph image.
if ($entity->getEntityTypeId() == 'paragraph' && $entity->bundle() == 'red_button') {
  if (!empty($build['field_button'][0]['#url'])) {

    $build['field_button'] = [
      '#markup' => '<a class="cta-button red-button" href="' . $build['field_button'][0]['#url']->toString() . '"><span>' . $build['field_button'][0]['#title'] . '</span></a>',
    ];
  }
}

if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'publication') {
  if (!empty($entity->field_source_link->getValue())) {
    $link = $entity->field_source_link->getValue()[0]['uri'];

  }
  else {
    $link = $entity->toUrl()->toString();
  }
  $build['link'] = ['#markup' => $link];
}

// Add easy access link for 1_up paragraph image.
if ($entity->getEntityTypeId() == 'field_collection_item' && $entity->bundle() == 'field_static_3_up_items') {
  if (!empty($build['field_static_item_link'][0]['#url'])) {
    $build['link'] = ['#markup' => $build['field_static_item_link'][0]['#url']->toString()];
  }
}

// Add easy access link for 1_up paragraph image.
if ($entity->getEntityTypeId() == 'field_collection_item' && $entity->bundle() == 'field_featured_publication') {
  if (!empty($build['field_publication_link'][0]['#url'])) {
    $build['field_publication_link'][0] = [
      '#type' => 'link',
      '#url' => $build['field_publication_link'][0]['#url'],
      '#title' => $build['field_title'][0]['#context']['value'],
      '#attributes' => ['class' => 'package--featured-research__title']
    ];
  }
}

if (!empty($build['field_header'][0]['#context']['value'])) {
  // Hack to get around field--field-header.html.twig.
  $build['field_header_raw'] = ['#markup' => $build['field_header'][0]['#context']['value']];
}