#Require Login

Require login authentication regardless of user permissions.

##Features

* Instantly require login to access any page
* Change default login and destination paths
* Exclude specific paths from login requirement
* Allow 403 (access denied) or 404 (not found) access
* Configure or disable access denied warning

##Installation

1. Download and install the module
2. Set "Administer login requirement" permission to appropriate roles
3. Configure the module: Admin > Config > People > Require login

**403 (access denied) OR 404 (not found) page access:**

You may want anonymous users to have view access on default or custom 403/404 pages. There's a special configuration
for just that. On the module configuration page open the "Advanced settings" section. Now check the desired exclude
boxes to enable access on that respective 403/404 page. All done!