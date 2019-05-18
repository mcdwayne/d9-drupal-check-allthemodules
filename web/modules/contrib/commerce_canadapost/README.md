Commerce Canada Post
=================

Provides Canada Post shipping rates and tracking functionality for Drupal Commerce.

## Module Setup

1. Use [Composer](https://getcomposer.org/) to get Commerce Canada Post with all dependencies: `composer require drupal/commerce_canadapost`

2. Enable module.

## Configuration

3. Go to /admin/commerce/config/shipping-methods/add:
  - Select 'Canada Post' as the Plugin
  - Select a default package type
  - Select all the shipping services that should be enabled
  - Click on 'API Authentication'
    - Enter the customer number, username, password and other optional config and save configuration.
  - Go to /admin/commerce/config/order-types/{COMMERCE_ORDER_TYPE}/edit
    - Select 'Canada Post' for the Shipment type and save

## Fetching Rates

1. Add a product to cart and checkout
2. Enter your shipping address and click on 'Calculate Shipping'
3. The estimated rates retrieved from Canada Post will now show up for the order

## Updating Tracking Information

Tracking summary for each shipment on an order can be seen in the order view page.

To add the tracking code received from Canada Post to a shipment:

1. Go to /admin/commerce/orders/{COMMERCE_ORDER_ID}/shipments

2. Click on the 'Edit' button under the appropriate shipment

3. Enter the tracking code received from Canada Post in the 'Tracking code' field and save.

Once a tracking code is added to a shipment, tracking summary is automatically updated when the shipment form is saved and also via cron.
It can also be done manually via the drush command: `drush cc-uptracking`.
