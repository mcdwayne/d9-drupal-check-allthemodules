<?php

//Theme for viewCardD8 views.

/**
 * Set default options.
 */
function viewCardD8_get_options($array) {
    $options = array();
    foreach ($array as $key => $value) {
        if (!is_array($value)) {
            $options[$key] = $value;
        }
        else {
            $options = array_merge($options, viewCardD8_get_options($value));
        }
    }
    return $options;
}



/**
 * Display a view as a byu card style.
 */
function template_preprocess_views_view_viewCardD8(&$vars) {

    $view = $vars['view'];
    $options = viewCardD8_get_options($view->style_plugin->options);
    $columns  = $options['columns'];
    $alignment = (isset($options['alignment']) ? $options['alignment'] : 'center');
    $border = (isset($options['border']) ? $options['border'] : 'none');
    $border_radius = (isset($options['border_radius']) ? $options['border_radius'] : 'none');

    if (!empty($vars['rows'])) {
        $vars['card_width'] = array();
        $widthClass = 'columns-' . $columns;
        $vars['card_width'] = $widthClass;
        $vars['cols'] = $columns;
        $vars['alignment'] = $alignment;
        $vars['border'] = $border;
        $vars['border_radius'] = $border_radius;
        foreach ($vars['rows'] as $id => $row) {
            $vars['rows'][$id]['content'] = $row;
        }
    }
}