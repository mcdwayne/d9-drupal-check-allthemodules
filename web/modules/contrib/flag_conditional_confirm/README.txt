Flag conditional confirm
========================

This module adds a "Conditional Confirm Form" link type for Flags. This
allows administrative users to set Flags to use the Confirm form only
under certain conditions.

For example, and administrator could set a Flag to require confirmation
when unflagging content but not when flagging.

The administrator will have this additional setting when configuring
the flag:

Conditional form shown only...
[x] On flagging
[ ] On unflagging
[ ] Custom condition (requires code)
On what condition the confirmation should form be shown.

If "Custom condition" is selected, then the module will look for
implementations of hook_flag_conditional_confirm_confirmation_required().
If this function returns TRUE, the Confirm form will be shown.
