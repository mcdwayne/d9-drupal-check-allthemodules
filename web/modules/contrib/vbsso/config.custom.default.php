<?php
/**
 * -----------------------------------------------------------------------
 * vBSSO is a solution which helps you connect to different software platforms
 * via secure Single Sign-On.
 *
 * Copyright (c) 2011-2017 vBSSO. All Rights Reserved.
 * This software is the proprietary information of vBSSO.
 *
 * Author URI: http://www.vbsso.com
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------
 *
 */

function vbsso_get_drupal_custom_config() {
    return array(
        'override-links' => true,
    );
}

/*
 * Please uncomment the line below only once if you change your 'override-links' to true or false to flash changes.
 * Please keep the line below commented to don't decrease the performance, because the menu_rebuild() is slow.
 * */

/*menu_rebuild();*/
