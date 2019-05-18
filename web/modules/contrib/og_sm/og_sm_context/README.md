# Organic Groups : Site Context
The Site Context module provides context detection based on:
* Site Content (site or site content).
* Site Administration pages.
* The "og_sm_context_site_id" query parameter.


## Functionality
This module supports this by providing following context detection:


### Site Content
This provider checks if a path starts with `node/{node}`. If so it will load the
node and checks if it is a Site or a Site Content. If so it will return the
related Site node as context.


### Site Administration pages
All Site Administration pages have a route like `/group/node/{node}/…`. This
context provider will detect these paths and use the `{group}` to check if this
is a Site node.


### Site Get parameter
This provider checks the presence of a get parameter `og_sm_context_site_id`.
This parameter should contain a Site's node ID. If set and valid, the
corresponding Site will be set as active.


## Requirements
* Organic Groups Site Manager



## Installation
1. Enable the module.
2. Configure the og_context providers (at the moment this can only be configured
   in the og.settings.yml file):
   * Enable the "**Site Content**" detection method and put it on the **first**
     place.
   * Enable the "**Site Administration**" detection method and put it on the
     **second** place.
   * Enable the "**Site Get parameter**" detection method and put it on the
     **third** place.
   * Enable or disable other context providers. Put them lower then the Site
     context providers.


> **NOTE :**
> The **Site Content** provider replaces the "Node" context provider as provided
> by the og_context module. This provider can be disabled.

> **NOTE :**
> The **Site Get parameter** provider should preferably only be used when
> the other providers of this module don't cover a specific use case.

> **TIP :**
> If the og_sm_path module is used, put the "**Site Path**" context handler
> first in the list of context handlers.

> **TIP :**
> If the og_sm_content module is used, the "URL (content create)" is no longer
> required to detect the context on node creation forms.
