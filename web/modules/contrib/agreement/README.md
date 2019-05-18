# Agreement

Agreement allows administrators to present a text-based agreement (think "Terms of Service") that users of a particular role must accept before they are allowed to access the site or specific pages on the site. Additionally agreement allows administrators to create multiple agreements targeting different roles or pages to collect user agreements.

The default agreement is presented to users right after login, and must be accepted before the user can navigate to another page. Users will still be able to access the homepage (`<front>`) and `/user/logout` without accepting the agreement; all other pages will redirect the user to the agreement acceptance form. The default agreement can be reconfigured or removed by administrators.

The following user permissions affect each agreement:

* Users with the "bypass agreement" role will never see an agreement.
* Users with the "revoke own agreement" role will be able to revoke their acceptance of any agreement from the agreement page.

The following options are configurable from the module's settings page:

* User role to which the agreement is restricted
* The agreement page title
* The agreement text
* The agreement page URL
* The success, revoked & failure messages presented to the user
* The checkbox & submit button texts

Additionally, theme developers can also override theme_agreement_page(), which is the theme function responsible for agreement page presentation.

There are modules ([Terms of Use](https://drupal.org/project/terms_of_use) and [Legal](https://drupal.org/project/legal)) which provide similar functionality during registration. The Agreement module provides the functionality to show an agreement to an existing user base, without requiring the users to re-register.

## Configuration

### Recommended Visibility Pages Configuration

It is important to set visibility pages correctly when displaying agreement all pages in order to support one-time login URLs.

The following is the recommended setting content for visibility pages:

```
<front>
user/*/edit
user/*
user
```

Additionally admin pages are not affected by the agreement.

