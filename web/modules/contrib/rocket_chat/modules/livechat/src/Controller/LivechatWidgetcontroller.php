<?php

namespace Drupal\livechat\Controller;

/**
 * Copyright (c) 2016.
 *
 * Authors:
 * - Lawri van Buël <sysosmaster@2588960.no-reply.drupal.org>.
 *
 * This file is part of (rocket_chat) a Drupal 8 Module for Rocket.Chat
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @file
 * Contains \Drupal\rocket_chat\Controller\Rocket.
 *
 * The main controller of our module.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\livechat\LivechatWidgetHandler;

/**
 * Class Rocket extends ControllerBase.
 *
 * @package Drupal\rocket_chat\Controller
 */
class LivechatWidgetcontroller extends ControllerBase {

  /**
   * Create widget.
   *
   * @return array
   *   Rendered widget.
   */
  public function createWidget() {
    $widget = new LivechatWidgetHandler('rocket_chat', 'rocket_chat_conf');
    return $widget->renderWidgetWithJavaScriptKeys(['server']);
  }

}
