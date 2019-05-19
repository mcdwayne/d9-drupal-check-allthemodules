<?php

/**
 * @file
 * Theme functions for Views Slideshow JCarousel
 */

use Drupal\Component\Utility\Html;

function _views_slideshow_jcarousel_preprocess_pager(&$vars) {
    // Render all the fields unless there is only 1 slide and the user specified
    // to hide them when there is only one slide.
    $vars['rendered_field_items'] = [];
    if (empty($vars['settings']['views_slideshow_jcarousel_pager']['hide_on_single_slide']) || $vars['count'] > 1) {
        // Build our javascript settings.
        $js_vars = array(
            'viewsSlideshowJCarouselPager' => array(
                $vars['vss_id'] => array(
                    $vars['location'] => array(
                        'carouselObj' => '',
                        'moveOnChange' => $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_move_on_change'],
                        'updateOnPrevNext' => $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_update_on_prev_next'],
                        'orientation' => $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_orientation'],
                        'scroll' => $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_scroll'],
                        'visible' => $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_visible'],
                        'wrap' => $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_wrap'],
                        'animation' => $vars['settings']['views_slideshow_jcarousel_pager_animation'],
                    ),
                ),
            ),
        );

        // Add the settings to the page.
        $vars['#attached']['drupalSettings'] = $js_vars;

        // Add our class to the wrapper.
        $vars['attributes']['class'][] = 'views_slideshow_jcarousel_pager';
        $vars['attributes']['class'][] = 'jcarousel-skin-' . $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_skin'];
        $vars['attributes']['id'] = 'widget_pager_' . $vars['location'] . '_' . $vars['vss_id'];
        $vars['attributes']['class'][] = 'test-jcarousel';

        foreach ($vars['view']->result as $count => $node) {
            $rendered_fields = [];
            foreach ($vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_fields'] as $field => $use) {
                if ($use !== 0 && is_object($vars['view']->field[$field])) {
                    $rendered_fields[] = [
                        '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_jcarousel_pager_field'),
                        '#view' => $vars['view'],
                        '#label' => $vars['view']->field[$field]->options['label'],
                        '#output' => $vars['view']->style_plugin->getField($count, $field),
                        '#css_identifier' => Html::cleanCssIdentifier($vars['view']->field[$field]->field),
                    ];
                }
            }
            $vars['rendered_field_items'][] = [
                '#theme' => $vars['view']->buildThemeFunctions('views_slideshow_jcarousel_pager_item'),
                '#vss_id' => $vars['vss_id'],
                '#item' => $rendered_fields,
                '#count' => $count,
                '#location' => $vars['location'],
            ];
        }
        $vars['#attached']['library'][] = 'views_slideshow_jcarousel/jcarousel.skins.' . $vars['settings']['views_slideshow_jcarousel_pager']['views_slideshow_jcarousel_pager_skin'];
        $vars['#attached']['library'][] = 'views_slideshow_jcarousel/views_slideshow_jcarousel';
    }
}

function _views_slideshow_jcarousel_preprocess_pager_item(&$vars) {
    $current = $vars['count'] + 1;
    $classes = [
        'views_slideshow_jcarousel_pager_item',
    ];
    if (!$vars['count']) {
        $classes[] = 'views_slideshow_active_pager_field_item';
    }
    $classes[] = ($vars['count'] % 2) ? 'views-row-even' : 'views-row-odd';

    $vars['attributes']['class'] = $classes;
    $vars['attributes']['id'] = 'views_slideshow_jcarousel_pager_item_' . $vars['location'] . '_' . $vars['vss_id'] . '_' . $vars['count'];
}
