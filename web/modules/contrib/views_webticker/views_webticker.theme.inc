<?php

/**
 * @file
 * Theme for webticker views.
 */
function template_preprocess_views_view_webticker(&$variables)
{
    $handler = $variables['view']->style_plugin;

    $options = $handler->options;

    $variables['attributes']['id'] = 'id_views_webticker';
    $variables['attributes']['class'][] = 'views_webticker';

    $settings['speed'] = $options['speed'];
    $settings['moving'] = $options['moving'];
    $settings['startEmpty'] = $options['startEmpty'];
    $settings['duplicate'] = $options['duplicate'];
    $settings['hoverpause'] = $options['hoverpause'];
    $settings['rssurl'] = $options['rssurl'];
    $settings['rssfrequency'] = $options['rssfrequency'];
    $settings['updatetype'] = $options['updatetype'];
    $settings['transition'] = $options['transition'];
    $settings['height'] = $options['height'];
    $settings['maskleft'] = $options['maskleft'];
    $settings['maskright'] = $options['maskright'];
    $settings['maskwidth'] = $options['maskwidth'];

    $attached = array(
        '#attached' => array(
            'library' => array('views_webticker/webticker-js'),
            'drupalSettings' => [
                'settings' => json_encode($settings)
            ]
        )
    );

    render($attached);
}