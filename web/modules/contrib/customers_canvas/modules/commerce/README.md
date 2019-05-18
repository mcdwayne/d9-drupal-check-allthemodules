# Customer's Canvas Commerce Integration

This module integrates the Customer's Canvas module into the Commerce checkout
process.

## Requirements

This module requires Drupal Commerce 1.x, all of it's dependencies, and the 
commerce_custom_product module (a UI for creating line item types).

While anonymous sessions are enabled for product customizations, it is
recommended that you require users to sign up before initializing their
Customer's Canvas session as it does require user ids to edit customizations.
We use session ids if a user is anonymous, but those sessions rarely last longer
than a few days.

## Setup Procedure

Go ahead and add the configuration values for all of the settings you can find
at /admin/config/services/customers_canvas, then follow the below procedure:

1. Add the following field types to a line item bundle of your choice. If you
   sell more than Customer's canvas products, you will likely need to create an 
   extra line item bundle. The following field types can be named anything.
   - "Customer's Canvas Download Link" (customers_canvas_commerce_link_download)
     - Must be unlimited value.
   - "Customer's Canvas Edit Link" (customers_canvas_commerce_link_edit)
   - "Customer's Canvas State ID" (customers_canvas_commerce_state_id)
   - If you created a new line item bundle, be sure to update the settings at
     /admin/config/services/customers_canvas with which line item bundle you 
     will be using.
2. Add the "Customer's Canvas Product JSON" (customers_canvas_product_json) 
   field to a product entity. Can be named anything.
3. Modify the display format of the "Customer's Canvas Product JSON" to show a
   link to start the customized build process. You may opt to hide the
   add-to-cart form altogether since it's presence isn't required for adding a
   customized product to cart.
4. Edit the cart view and the checkout summary cart view to show the new line
   item fields for downloading and editing. For example, go to the /cart page
   and click the gear icon in the upper right to edit the view. Add a field for
   the download link by searching the available fields for the field you added.
   Save the view. When you have a new product added to your cart that has a
   download link, it should now appear. Repeat this process for the edit link.
5. Optionally modify the order view pages to show edit and download buttons as
   well. Administrators should be able to edit customer's products, but beware
   that anonymous product canvases may not load without additional work.

## Expected Checkout Workflow

1. Create a product with a JSON string.
2. Click the rendered product link for the customer's canvas build link.
3. Modify the canvas.
4. Click "Finish"
   - Optionally alter the form to change the button text to whatever you want.
5. You should be redirected to the page you specified on the settings page.