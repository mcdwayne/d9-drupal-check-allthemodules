<?php

/**
 * @file
 * File for the Cloudinary transformation convert features.
 */

/**
 * Convert image effect image_crop to cloudinary style.
 */
function cloudinary_transformation_image_crop($effect, $exist_effect, $resource)
{
    $search = array(
        'left-top',
        'center-top',
        'right-top',
        'left-center',
        'center-center',
        'right-center',
        'left-bottom',
        'center-bottom',
        'right-bottom',
    );
    $replace = array(
        'north_west',
        'north',
        'north_east',
        'west',
        'center',
        'east',
        'south_west',
        'south',
        'south_east',
    );
    $anchor = str_replace($search, $replace, $effect['data']['anchor']);
    $effect['data']['gravity'] = $anchor;
    unset($effect['data']['anchor']);
    // Default with crop.
    $crop = 'crop';
    // Assign destination & original size.
    $dst_width = $effect['data']['width'];
    $dst_height = $effect['data']['height'];
    $src_width = $resource['width'];
    $src_height = $resource['height'];
    // Init crop data.
    $crop_data = array();
    // Set image background with black.
    $effect['data']['background'] = '#000';
    // If destination width & height large than original, mpad.
    if ($dst_width > $src_width && $dst_height > $src_height) {
        $crop = 'mpad';
    }
    // If destination width large, height small;
    // Crop with original width & destination height;
    elseif ($dst_width > $src_width && $dst_height <= $src_height) {
        $crop_data = array('width' => $src_width, 'height' => $dst_height);
    }
    // If destination width small, height large;
    // Crop with destination width & original height;
    elseif ($dst_width <= $src_width && $dst_height > $src_height) {
        $crop_data = array('width' => $dst_width, 'height' => $src_height);
    }
    // Unset background attribute.
    else {
        unset($effect['data']['background']);
    }
    // Add crop mode first if have crop data;
    // Add pad mode with destination size and black background.
    if (!empty($crop_data)) {
        $data = array(
            'type' => CLOUDINARY_STREAM_WRAPPER_TRANSFORMATION_MULTIPLE,
            'data' => array(
                cloudinary_transformation_image($crop_data, array('crop' => 'crop')),
                cloudinary_transformation_image($effect['data'], array('crop' => 'pad')),
            ),
        );

        return $data;
    }

    return cloudinary_transformation_image($effect['data'], array('crop' => $crop));
}

/**
 * Convert image effect image_desaturate to cloudinary style.
 */
function cloudinary_transformation_image_desaturate($effect, $exist_effect, $resource)
{
    if (!isset($exist_effect['effect'])) {
        $effect['data']['type'] = CLOUDINARY_STREAM_WRAPPER_TRANSFORMATION_APPEND;
    } elseif ($exist_effect['effect'] == 'grayscale') {
        return false;
    }

    return cloudinary_transformation_image($effect['data'], array('effect' => 'grayscale'));
}

/**
 * Convert image effect image_resize to cloudinary style.
 */
function cloudinary_transformation_image_resize($effect, $exist_effect, $resource)
{
    return cloudinary_transformation_image($effect['data'], 'scale');
}

/**
 * Convert image effect image_rotate to cloudinary style.
 */
function cloudinary_transformation_image_rotate($effect, $exist_effect, $resource)
{
    if (!isset($exist_effect['crop']) && !isset($exist_effect['angle'])) {
        $effect['data']['type'] = CLOUDINARY_STREAM_WRAPPER_TRANSFORMATION_APPEND;
    }
    // Convert short #FFFFFF syntax to full #FFF syntax.
    $bg = $effect['data']['bgcolor'];
    unset($effect['data']['bgcolor']);
    if (strlen($bg) == 7) {
        if ($bg[1] == $bg[2] && $bg[3] == $bg[4] && $bg[5] == $bg[6]) {
            $bg = $bg[0] . $bg[1] . $bg[3] . $bg[5];
        }
    }
    if ($bg) {
        $effect['data']['background'] = $bg;
    }

    $degrees = $effect['data']['degrees'];
    if (!empty($effect['data']['random'])) {
        $degrees = abs((float) $degrees);
        $degrees = rand(-1 * $degrees, $degrees);
    }
    $effect['data']['angle'] = $degrees;
    // Unset unused values.
    unset($effect['data']['bgcolor'], $effect['data']['degrees'], $effect['data']['random']);

    return cloudinary_transformation_image($effect['data']);
}

/**
 * Convert image effect image_scale to cloudinary style.
 */
function cloudinary_transformation_image_scale($effect, $exist_effect, $resource)
{
    $crop = 'fit';
    unset($effect['data']['upscale']);
    // If destination width & height large than original, lfill.
    if ($effect['data']['width'] > $resource['width'] && $effect['data']['height'] > $resource['height']) {
        $crop = 'lfill';
    }

    return cloudinary_transformation_image($effect['data'], $crop);
}

/**
 * Convert image effect image_scale_and_crop to cloudinary style.
 */
function cloudinary_transformation_image_scale_and_crop($effect, $exist_effect, $resource)
{
    return cloudinary_transformation_image($effect['data'], 'fill');
}
