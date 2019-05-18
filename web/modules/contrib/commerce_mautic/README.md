# Commerce Mautic

Adds integration with Mautic to enable following features:
 * Create a mautic contact from a completed order. The contact information can be extended by implementing a hook.
 * Trigger sending a mautic email to the created contact by specifying an email template id.

Has Mautic API as a dependency.

## Installation and basic usage
 * Add the module as usual and activate.
 * Configure integration under /admin/commerce/config/mautic.
 
## Add custom order data to mautic.
 * See "hook_commerce_mautic_order_data_alter" in "commerce_mautic.api.php" for details.
