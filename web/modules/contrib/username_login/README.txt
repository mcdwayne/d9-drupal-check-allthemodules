Role based Username Login

This module allows users to login using only user name with the selected Role.

If your application doesn't contains any sensitive data or your user don't have any administrative permissions, configure the role to allow login with user name only then the users having only the configured role can login with their registered user name.

Installation Instructions:
1. Enable the module as you normally would.
2. Create a new Role(s). (authenticated and administrator roles is excluded).
3. Go to /admin/config/people/accounts/username-login and select Role(s).
4. Go to /admin/structure/block and assign "Username Login" block to particular regions on theme.

WARNING:
- This module has security implications, because it allows you to log in with a user name.
- Any user can login as another user, just knowing the user name.

Similar Project
- Email Login