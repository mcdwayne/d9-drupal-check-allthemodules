Entity Route Context

https://www.drupal.org/project/entity_route_context
Copyright Daniel Phin (@dpi) https://www.drupal.org/u/dpi 2019

This project provides a service and context to determine if the current route
match is owned by a particular entity type, by way of link templates.

A context is also provided for plugins and other to consume. For example you may
want to create a block, or DsField, that relies on a particular entity from the
route. It operates in an entity type agnostic way, similar to the
`node.node_route_context` context. Entity type specific contexts are also
available in case a plugin is designed to take any entity type, but as a site
builder you may choose for it to respond to a particular entity type.

# License

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software 
Foundation; either version 2 of the License, or (at your option) any later 
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with 
this program; if not, write to the Free Software Foundation, Inc., 51 Franklin 
Street, Fifth Floor, Boston, MA 02110-1301 USA.
