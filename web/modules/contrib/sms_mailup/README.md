Integrates MailUp with SMS Framework.

Copyright (C) 2016 Daniel Phin (@dpi)

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

 1. Install module as per [standard procedure][drupal-module-install].
 2. This module has composer dependencies, see [instructions][composer-dependencies]
    for information on how to resolve these dependencies.

# Configuration

 1. Create a gateway instance (_/admin/config/smsframework/gateways_)
 2. Use 'Mailup' for the plugin type.
 3. After the form saves, fill in each required form. Including Account,
    Access Keys, List, and Campaign Code.
 4. Save the form.
 5. Go to the 'OAuth' tab on your new gateways' edit page.
 6. Click the 'Request token' link. You will then be forwarded to the
    Mailup website to initialise OAuth authentication.
 7. After authenticating, you will be returned to your website and see a
    successful authentication message.
 8. Your gateway is now configured.

[drupal-module-install]: https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules "Installing Contributed Modules"
[composer-dependencies]: https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies "Installing modules' Composer dependencies"
