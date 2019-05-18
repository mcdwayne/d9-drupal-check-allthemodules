D8 upgrade notes

This file will track (meta) tasks that need to be handled before 8.x release.

Info.yml file:
  - removed ctools dependency. Do we actually need it?
  - removed files section. These should be (lazily) autoloaded by the core
  - removed data_ui module. Part of the main module now.