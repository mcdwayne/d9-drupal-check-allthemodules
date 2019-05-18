#React Block module
This module extends the [PDB module](https://www.drupal.org/project/pdb "PDB module") _(which is a dependency)_ to add some additional functionality. This module aims to add some more structure around React components and Redux state management in Drupal.

React blocks are defined by adding a simple YML file to your _exising_ React component. This allows front end devlopers to create and manage React components as they normally would, and by including this YML file they can make the component available inside Drupal.

The component is available to a Drupal site builder as a block in the UI. It can be placed normally using core block placement, panels, layout builder, or any other method. As far as Drupal is concerned, its just another block.

Additionally, there is the capability for the site builder to add optional configuration parameters to the block that will be exposed to React. This allows developers to make more advanced and reusable components.

> Example: Sarah has created a react block (component) that takes a product ID as a parameter. When the block is rendered by the browser, React calls the ecommerce API to get the product information and render a product info card with an "Add to Cart" button.

>When the site builder places the React block on a page, they can choose which product is loaded from the ecommerce API by configuring that instance of the block. This allows the site builder to reuse this block over and over for any number of products.

>Now Sarah only has a single component to manage, and when she updates the component, all instances of it are updated across the site.

## Features
- Field that lets you add a react block to an entity (super cool!)
- Updated React versions
-- React 16.8.2
-- React DOM 16.8.2
- Redux support
-- Redux 4.0.1
-- React Redux 6.0.0

### TODO
- Admin config option for CDN library
- Find a better way to manage the libraries  (currently a manual process)
- Find a better way to manage unique componentID
- Restrict visibility of components based on parent module
- More sample components (open an issue if you have a good, useful idea)
-- Amazon product? Wikipedia article? Something Drupal-ly?
- Additional support for Redux to make it easier to tie into state management
- Extended support for block config options other than just text
- Alter hook options for block config
- Inject block config into React component by default
- Investigate Thunk middleware management
