Better sub-themes
=================

The Better sub-themes module improves the inheritance features for Drupal
themes, providing the ability for a sub-theme to inherit it's base themes block
layout among other things.



Features
--------

- Inherit base theme block layout: 

  ```
  better subthemes:
    block layout: true
  ```


- Inherit and re-map base theme block layout:

  ```
  better subthemes:
    block layout: true
    block layout remap:
      [SOURCE1]: [DESTINATION1]
      [SOURCE2]: [DESTINATION2]
      ...
  ```

  **Example:** 
  This will move blocks placed in the base themes `sidebar_first` region to the
  sub-themes `sidebar_second` region.
  
  ```
  better_subthemes:
    block layout: true
    block layout remap:
      sidebar_first: sidebar_second
  ```


Installation / Usage
--------------------

- Install as per usual: https://www.drupal.org/node/1897420
- All configuration (as per above) is made in the `*.info.yml` file.



@TODO / Roadmap
---------------

- Add ability to inherit regions of base / source theme.
- Add ability to configure via theme settings.
- Add ability to override inheritance.
