Entity Domain Access
============

Description
-----------

This is module provide domain access control for any fieldable entities.

Requirements
------------
You will need the `domain_access` module to be enabled.

Installation
------------
Install the module as any other module.

An admin user should use `administer domains` permission to manage the module settings.

Usage
-----
Go to settings page `admin/structure/domain/entities` and enable entity types to manage domain access.
After enabling entity types you can configure domain access for bundles of enabled entity types.

You can choose for each bundle the assignation behavior you want to use :

- Affiliate automatically created entity to a value
	(no widget on entity creation form, auto-assignation)
- User choose affiliate, with a default value
	(form widget on the entity creation form)

Then you can set access permissions for roles. The module provide premissions that based on following operations:

- create
- update
- view
- view unpublished (only if entity type has method `isPublished()`)
- delete

Each operation has following permissions:

- allow on any domains for whole entity type (include all bundles)
- allow on assigned for whole entity type (include all bundles)
- allow on any domains for specific bundle
- allow on assigned for specific bundle

Assign role to specific user and try to perform some operations to check.


Views integration
-----------------
All managed bundles has new views filter `Current domain` that allow to check availability of entity instance on current domain. Just add the filter and select `Yes`/`No` to filter view by current domain.

*Note*: Usage of this filter force add `DISTINCT` to views query.

Alternative solutions
---------------------
[Domain access](https://www.drupal.org/project/domain) module provide own domain access functions for `User` and `Node` entity types.

[Widget Engine](https://www.drupal.org/project/widget_engine)  module provide own domain access functions for `Widget` entity type.

[Domain Access Entity](https://www.drupal.org/project/domain_entity) is simmilar project to provide domain access functions for any entities.

Ignore entity types
-------------------
Some entity types can be ignored to use another solutions of domain access management.
After installations the config contain following entity types to ignore:

- `node` - should use `domain_access` module
- `user` - should use `domain_access` module
- `widget` - should use `widget_engine_domain_access` module

TODO
----
- Add tests
- Create config page for ignore list

Thanks
-------
Config of this module based on code of [Domain Access Entity](https://www.drupal.org/project/domain_entity) module.

Maintainers
-----------
Current maintainers:

  * [Vyacheslav Malchik (validoll)](https://www.drupal.org/u/validoll)
