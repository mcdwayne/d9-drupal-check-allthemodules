# Organic Groups : Site User
This module provides user features.


## Functionality

### Feature: Profile page
This module provides a feature that allows a user to have a profile within a
site context.



## Requirements
* Organic Groups Site Manager



## Installation
1. Enable the module.
2. Enable the "User profile" feature at `[site-path]/admin/features`.



## API
### Get the path to the user profile
Helper to get the path to the user profile.

* If a Site context is active and that Site has the User Profile feature enabled
  => Site User profile.
* Else => the default Account page.

```php
$path = og_sm_user_profile_path($account);
```
