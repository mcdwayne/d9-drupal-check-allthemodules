RNG date scheduler.

Control whether a user can register for an event based on the current time
relative to date fields attached to the event entity.

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

# Installation

 1. Enable module.
 2. Add date fields to your event entity type (examples: start date, end date)
 3. Edit the Event Type associated with your event entity type
 4. Go to 'Date scheduler' tab.
 5. Configure the form.
 6. Ensure you check the 'Enabled' checkbox adjacent to your date fields.

# Debugging

The module provides a user interface for each event explaining how dates
on the event affect the ability to create new registrations. You can find this
interface in the event settings:

 1. View your event entity.
 2. Go to 'Event' tab.
 3. Go to 'Dates' sub tab.

The output of this page can be controlled by modifying the values of date fields
on the event entity.
