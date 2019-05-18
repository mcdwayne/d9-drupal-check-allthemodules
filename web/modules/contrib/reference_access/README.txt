Drupal module: Reference Access

INTRODUCTION
==============================
Reference Access allows restricting access to content unless a user references that content (via an entity reference field on users).

Also, the access check can go one level deeper: It can check if the content is referenced by content that is referenced by the user.

The reference checks are done only on selected content types; all not-selected content types are ignored. These are chosen in the module settings at /admin/config/reference_access/config.

There is also a permission that allows specific roles to bypass all reference checks.

Lastly, reference fields are hidden on all user edit forms except for roles that have the bypass permission (such as administrators).



FAQS
==============================
 Q: 
 A: 



TROUBLESHOOTING
==============================
If you have any troubles, you may contact the maintainers directly.



MAINTAINERS
==============================
 * Dane Rossenrode, founder and owner of Touchdreams (https://touchdreams.co.za)