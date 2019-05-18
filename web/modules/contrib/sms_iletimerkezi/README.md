Enables modules to use Iletimerkezi API, and integrates with SMS Framework.

Copyright (C) 2018 Enes Ozden (@ensozden)

# License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

# Installation

 1. Download [SMS Framework][sms-framework] and its dependencies.
 2. Install module as per [standard procedure][drupal-module-install].
 3. This module has composer dependencies, see
    [instructions][composer-dependencies]
    for information on how to resolve these dependencies.

# Create a Iletimerkezi API.

 1. Log in to iletimerkezi.com.
 2. Click 'Settings' from the menu.
 3. Add a name to the api and click 'Create new api key' button.
 4. Copy the text in the public key and private key columns.

# Configuration

 1. Create a SMS gateway plugin at
    _/admin/config/smsframework/gateways_.
 2. Fill out the form, click 'Save' button.
 3. The page will reload, fill out the public key,
    private key and sender fields. Click 'Save'.

# Testing

If you need to test, you should take advantage of the _SMS Devel_ module
bundled with _SMS Framework_. It is accessible at _Configuration » Development »
Test SMS_.

[sms-framework]: https://drupal.org/project/smsframework
[drupal-module-install]:
https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules
"Installing Contributed Modules"
[composer-dependencies]:
https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies
"Installing modules' Composer dependencies"
