<?php

namespace Drupal\formassembly\Plugin\KeyType;

use Drupal\key\Plugin\KeyType\AuthenticationMultivalueKeyType;

/**
 * Key module plugin to define a credentials KeyType for FormAssembly module.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 *
 * @KeyType(
 *   id = "formassembly_oauth",
 *   label = @Translation("FormAssembly Oauth"),
 *   description = @Translation("A key type to store oauth credentials for the FormAssembly module. Store as JSON with fields 'cid' and 'secret'"),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "textarea_field"
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "cid" = @Translation("Client ID"),
 *       "secret" = @Translation("Client Secret")
 *     }
 *   }
 * ) */
class FormAssemblyOauth extends AuthenticationMultivalueKeyType {}
