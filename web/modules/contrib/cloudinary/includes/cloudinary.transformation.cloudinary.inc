<?php

/**
 * @file
 * File for the Cloudinary transformation convert features.
 */

/**
 * Convert image effect cloudinary_crop to cloudinary style.
 */
function cloudinary_transformation_cloudinary_crop($effect, $exist_effect, $resource)
{
    $data = cloudinary_prepare_transformation($effect['data'], false);
    /**
     * We check to see if a crop value has been set in the effect array and if so we set it to the $crop variable.
     */
    if ($effect['data']['crop'] != null) {
        $crop = $effect['data']['crop'];
    } else {
        $crop = null;
    }
    if (isset($data['multiple'])) {
        unset($data['multiple']);
        $new_data = array('type' => CLOUDINARY_STREAM_WRAPPER_TRANSFORMATION_MULTIPLE, 'data' => array());
        foreach ($data as $d) {
            $new_data['data'][] = cloudinary_transformation_image($d);
        }
        return $new_data;
    }
    return cloudinary_transformation_image($data, $crop);
}
