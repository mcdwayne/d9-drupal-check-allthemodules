This module helps you separate the login page for back office and front office.
Besides that, it's very helpful when a guest accesses a non-authorized page, he
will be redirected to login page instead of having a 403 page.

For example: when a guest tries to access to admin/config. He'll be redirected
to admin/login?destination=admin/config. After logging in, he'll be redirected
to admin/config.

Installation

Download the module and simply copy it into your contributed modules folder:
[for example, your_drupal_path/sites/all/modules] and enable it from the
modules administration/management page.
More information at: Installing contributed modules (Drupal 7)

Configuration

After successful installation, go to settings page
admin/config/system/smart-login to change what you like.

Options:
Admin Settings (for Back Office):
- Theme: The theme to be used when user goes to admin/login
- Login destination: The destination after user logins if there is no defined
  destination in url.
- Logged in redirect: This page is displayed when a logged-in user goes to
  admin/login.
Frontend Settings (for Front Office):
- Login destination: The destination after user logins if there is no defined
  destination in url.
