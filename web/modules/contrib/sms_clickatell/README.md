Enables modules to use Clickatell API, and integrates with SMS Framework.

Copyright (C) 2018 Daniel Phin (@dpi)

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
 3. This module has composer dependencies, see [instructions][composer-dependencies]
    for information on how to resolve these dependencies.

# Create a Clickatell API.

 1. Log in to Clickatell. When you are logging in you should be using 
    'SMS Platform'. Not 'Communicator / Central'. If your account logs in with
    'Communicator / Central', then you should change your SMS Clickatell module
    install to the 1.x branch.
    After logging in, your Clickatell panel should have the 
    portal.clickatell.com domain. If it is central.clickatell.com, then you are
    using the wrong version of this module.
 2. Click 'SMS integrations' from the menu.
 3. Click 'Create new integration' button.
 4. Add a name to the integration, then select 'REST' for _API Type_
 5. Click the 'Next' button, change any options you like, until you reach
    'Save integration' step. Then click 'Finish' button.
 6. Copy the text in the _API key_ column next to your newly created
    integration. Then see Configuration section below.    

# Configuration

 1. Create a SMS gateway plugin at _/admin/config/smsframework/gateways_.
 2. Fill out the form, click 'Save' button.
 3. The page will reload, fill out the _Authorization token_ field. Click 
    'Save'.
    
# Testing

If you need to test, you should take advantage of the _SMS Devel_ module
bundled with _SMS Framework_. It is accessible at _Configuration » Development »
Test SMS_.

[sms-framework]: https://drupal.org/project/smsframework
[drupal-module-install]: https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules "Installing Contributed Modules"
[composer-dependencies]: https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies "Installing modules' Composer dependencies"
