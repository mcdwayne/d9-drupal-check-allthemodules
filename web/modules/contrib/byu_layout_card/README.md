# Layout Card Module

## Getting Started
Enable the module and it's dependencies. Go to a content type or other entity's 
Manage Display tab. Under Layouts you will see the Layouts 'BYU Card' and 
'BYU Feature Card'.

## How it Works
This module utilizes the byu-card and byu-feature card components. This module
provides an interface and provides a bridge between the html web components and Drupal.

It creates a display layout which you can access in your content display screens (and 
other entities that have display settings.) 

When you tell one of your content type's view modes (Default,  Teaser, etc) to use 
the BYU Card or BYU Feature Card layouts, you'll see the appropriate regions. 

## How to Use

1. Installation will create two view modes.
2. Go to the content type you wish to display through a BYU Card format, and go to Manage Display. At the bottom, enable the BYU Card and/or BYU Feature Card modes.
3. Edit that display mode, select the Layout 'BYU Card' or 'BYU Feature Card' at the bottom of the screen and save. 
4. Drag your fields appropriately into the corresponding regions. Hide all labels.

Note: We recommend you use the module manage display to allow displaying the title and other native node features inside a region in the display mode.
     https://www.drupal.org/project/manage_display

### Slots in the Components
BYU Feature Card contains several regions. See it's readme for more information:
https://github.com/byuweb/byu-feature-card-component

### Customizing the BYU Card
There are several options that you can manipulate in BYU Card and BYU Feature Card 
by adding classes or attributes to the byu-card or byu-feature-card elements.

BYU Card: https://github.com/byuweb/byu-card

BYU Feature Card: https://github.com/byuweb/byu-feature-card-component 

1. Create a field for your content type. Select text (plain) and call it 
"Classes" or "Card Classes". 
2. Go to your Manage Display tab for the content type. Drag that field to 
the Classes region. Click Save, and clear caches if needed.
3. Edit a node that you are working on and enter a valid class in the Classes field. 
View that node in the display mode you are working with. (i.e. Default or teaser). 
You should see your class applying to byu-card or byu-feature-card.

Repeat this process for the Attributes field and region. 

## Run into a Problem?
If you are using a unique field type and it doesn't go into the component correctly,
let the web community know by posting an issue in github:
https://github.com/byuweb/byu_layout_card/issues

Please include as many details as possible about what you are doing, what modules you 
are using and anything else applicable.