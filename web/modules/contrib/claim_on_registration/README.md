
Summary
If you allow anonymous content creation, this module handles the Claim of the
content if the anonymous user Logs in or registers an account.

Installation
  Install in the usual way.

Similar D7 Module
inspiration was taken from https://www.drupal.org/project/create_and_reg.

Configuration
  Go to admin/config/claim-on-registration or Configuration > Content authoring
  > Claim on registration.
  Select your content Types. Note you must enable the create content type
  permission for the selected content types for anonymous users.
  Enter Your cookie expiry value.
  Enter your cookie name.
  and save.

Process
    Anonymous user creates a content which you set permission for.
    The module simply sets a cookie to with the new id, or appends the cookie
    if the anonymous creates multiple
    The user registers or logs in.
    The created node(s) will be assigned to the user after logging in or
    registration.
    At this point when the user is assigned the content this module provides
    a hook which passes the node object with the updated user id.
    You may use this hook to handle any custom configurations you may require.


Example hook usage:
/**
 * Implements hook_claim_on_registration_node_update().
 */
function MYMODULE_claim_on_registration_node_update($node) {
  if (is_object($node)) {
    $type = $node->getType();
    if ($type == 'your_conten_type') {
      // GET New or logged in user id.
      $uid = $node->getOwnerId();

      // Get some other value of a custom field.
      $field = $node->get('field_some_field')->value;
      // Party!

      // No need to call $node->save().
      }
    }
  }
}

@TODO:
  The future of this module is to create proper dependency injected classes to
  bring code out of the .module file.
  Give a Security over hall to use sessions so that the cookie can not
  be altered as easy.
