Prevent registrations when a registrant is registered for a similar event.

Copyright (C) 2016 [Daniel Phin](http://dpi.id.au) ([@dpi](https://www.drupal.org/u/dpi))

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

# Notice

This module is UNSUPPORTED in ALL WAYS imaginable. Do not expect any upgrade
paths.

Things may not work. Your site may break and/or explode. Use with caution.

# Installation

Make sure you are running RNG 8.x-1.0-rc4 or later.

# Configuration

 1. Go to Administration » Structure » Event types
 2. Select fields used to calculate similar (conflicting) events.
 3. Go to the 'Conflicts' tab located inside an event to see conflicting events.
    Registrants will be prevented from registering for the viewed event if they 
    are also registered for the listed events.
