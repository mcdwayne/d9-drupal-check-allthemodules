========================================
  Role Weights - role_weights
  README.txt
========================================

A small utility module providing a simple API and core token support to Drupal's role
weights. It's not much use on its own, more of a helper module for other modules which
require this functionality.

Role Weights project on drupal.org: http://drupal.org/project/role_weights

Please post issues and suggestions to the issue tracker: http://drupal.org/project/issues/role_weights


Installation
========================================
1. Upload the role_weights directory to your modules directory.
2. Enable the module under Administration -> Modules.
3. Visit Administration -> People -> Permissions -> Roles to change role weights (if
   you haven't done so already).


Usage
========================================
Once installed and role weights have been set, modules and themes can make use of:

function role_weights_get_weight($role_id)
- gets the weight value for a specified role

function role_weights_get_weighted_max($roles, $weight_end)
- takes an array of role_id => role_name and returns the 'lightest' (ie closest to -infinity)
  role id when $weight_end parameter is 'lightest' or the 'heaviest' (ie closest to +infinity)
  role id when $weight_end is 'heaviest'


NO LONGER AVAILABLE in 7.x branches and above:

function role_weights_get_highest($roles)
- takes an array of role_id => role_name and returns the 'highest' role id

This function has been replaced by role_weights_get_weighted_max($roles, 'lightest') and
was previously deprecated in 5.x and 6.x releases, only remaining as a wrapper for backwards
compatibility. If your module/code relies on Role Weights, please update accordingly.


Notes
========================================
CAUTION! Do NOT allow lower-level roles to edit role weights if another module is relying
on it to control any higher-level stuff like access permissions, site settings and development
modules. I wouldn't really advise this in the first place - this module is intended for simple
selection of roles based on weights for use in, for example, theming usernames & profiles or
choosing the 'highest' from multiple roles.
