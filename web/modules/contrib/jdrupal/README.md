## Setup

### Setting up the jDrupal module

1. Download and enable the jDrupal module as usual: `drush en jdrupal`
2. On your Drupal site, go to `admin/config/services/rest`
3. Enable the `jDrupal Connect` resource
4. Check the box next to the `GET` method
5. Under `GET`, check `json` for `Supported formats` 
6. Under `GET`, check `cookie` for `Authentication providers`
7. Click `Save configuration`
8. Enable the `User` resource
9. Then enable `GET + json + cookie` and `POST + json + cookie`
10. Click `Save configuration`
11. Optional, but recommended: The `Content` resource will already be enabled, but edit it to include `json + cookie` on at least `GET` (see the `Entity C.R.U.D.` section below)
12. Click `Save configuration`

### Setting up jDrupal user permissions

1. On your Drupal site, go to `admin/people/permissions`
2. Under `RESTful Web Services` grant permissions for/to:
  1. Access GET on jDrupal Connect resource : Anonymous user, Authenticated user
  2. Access GET on User resource : Authenticated user
  3. Optional, but recommended (see the `Entity C.R.U.D.` section below):
    1. Access GET on Content resource : Anonymous user, Authenticated user
    2. Access POST on Content resource : Authenticated user
    2. Access PATCH on Content resource : Authenticated user
    2. Access DELETE on Content resource : Authenticated user
3. Click `Save permissions`
4. Clear all of Drupal's caches: `drush cr`

## Setting up Entity C.R.U.D.

Depending on your app's needs, consider enabling other entity resources such as `Content` and `Comment`.

When enabling the methods for a particular resource, always use `json + cookie` for the supported format and authentication provider, respectively.

C: Create - `GET`
R: Retrieve - `POST`
U: Update - `PATCH`
D: Delete - `DELETE`

Once you enable a resource and method, head back to `admin/people/permissions` to grant access to them for your desired user roles.

## Getting started with jDrupal

Try the Hello World or browse the documentation.

- http://jdrupal.easystreet3.com/8/docs/Hello_World
- http://jdrupal.easystreet3.com/8/docs
- http://jdrupal.easystreet3.com/8/api

