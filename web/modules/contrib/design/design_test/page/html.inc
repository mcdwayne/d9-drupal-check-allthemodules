<?php

/**
 * @file
 * Random content to test styling of basic HTML/typography.
 */

/**
 * Tests typography.
 */
function design_test_page_html() {
  drupal_set_title(design_test_create_greeking(rand(2, 10), FALSE));

  // Headings.
  $page['h1'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h1',
    '#value' => design_test_create_greeking(rand(1, 6), FALSE),
  );
  $page[] = array('#type' => 'p', design_test_create_greeking(40));
  $page['h2'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => design_test_create_greeking(rand(1, 6), FALSE),
  );
  $page[] = array('#type' => 'p', design_test_create_greeking(30));
  $page['h3'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h3',
    '#value' => design_test_create_greeking(rand(1, 6), FALSE),
  );
  $page[] = array('#type' => 'p', design_test_create_greeking(40), design_test_create_greeking(20));
  $page['h4'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h4',
    '#value' => design_test_create_greeking(rand(1, 6), FALSE),
  );
  $page[] = array('#type' => 'p', design_test_create_greeking(50));
  $page['h5'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h5',
    '#value' => design_test_create_greeking(rand(1, 6), FALSE),
  );
  $page[] = array('#type' => 'p', design_test_create_greeking(50));
  $page['h6'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h6',
    '#value' => design_test_create_greeking(rand(1, 6), FALSE),
  );
  $page[] = array('#type' => 'p', design_test_create_greeking(50));

  // Some more paragraphs.
  $page['paragraphs'] = array(
    '#type' => 'p',
    design_test_create_greeking(70),
    design_test_create_greeking(20),
    design_test_create_greeking(40),
  );

  // Lists.
  $page['lists'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => 'Lists',
  );
  $list = array(
    design_test_create_greeking(10),
    design_test_create_greeking(30),
    array(
      'data' => design_test_create_greeking(10),
      'children' => array(
        design_test_create_greeking(8),
        design_test_create_greeking(16),
        design_test_create_greeking(8),
      ),
    ),
    design_test_create_greeking(10),
  );
  $page['ul'] = array(
    '#theme' => 'item_list',
    '#title' => 'Unordered list',
    '#items' => $list,
  );
  $page['ol'] = array(
    '#theme' => 'item_list',
    '#title' => 'Ordered list',
    '#items' => $list,
    '#type' => 'ol',
  );

  // Definition list.
  $page['dl'] = array(
    '#markup' => '<h3>Definition list</h3>
      <dl>
        <dt>' . design_test_create_greeking(1, FALSE) . '</dt>
        <dd>' . design_test_create_greeking(40) . '</dd>
        <dt>Another Term</dt>
        <dd>Another Definition
          <p>This one has a paragraph inside as well as a list:</p>
          <ul>
            <li>Milk</li>
            <li>Eggs</li>
            <li>Cheese</li>
          </ul>
        </dd>
        <dt>' . design_test_create_greeking(5, FALSE) . '</dt>
        <dd>' . design_test_create_greeking(40) . '</dd>
      </dl>',
  );

  // Preformatted text.
  $page['pre_heading'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => t('Code wrapped in &lt;pre&gt;'),
  );
  $page['pre'] = array(
    '#type' => 'html_tag',
    '#tag' => 'pre',
    '#value' => '#header {
  min-height: 100px;
}
/* ' . design_test_create_greeking(30) . '*/
#main-wrapper {
  background: #fff;
  color: #111;
}
a#logo, #site-name, #site-slogan {
  display: inline;
  margin-right: 15px;
}
#site-name {
  font-size: 16px;
}
#site-name a {
  color: #fff;
  text-decoration: none;
}',
  );

  return $page;
}
