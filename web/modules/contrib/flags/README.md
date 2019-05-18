# CONTENTS OF THIS FILE
-----------------------------------------------------------------------

* OVERVIEW
* USAGE
* REQUIREMENTS
* INSTALLATION

# OVERVIEW
-----------------------------------------------------------------------
The flags module provide mapping of flag icons with countries and languages.
The mappings are stored in the main module, and different sub-modules provide
integration with other modules such as country, langaugefield and the
language field provided by core.

The flags can be displayed in the formatter of the field, and even in the
form display if you're using select_icons module.


# USAGE
-----------------------------------------------------------------------

To use this module you need to enable one of the submodules (or other modules)
that provide integrations. After you enable the submodules for country,
languagefield or language field (core), you will be able to see new
field formatters for flag icons under "Manage display" configurations (and also
under "Manage form display" if you're using select_icons module).

If you enable the UI submodule you can also change what flag icons that should
be used for different countries and languages. The UI can be found under
Administration -> Configuration -> Regional and language -> Flags

# REQUIREMENTS
-----------------------------------------------------------------------
This module requires select_icons module if you want to display flag icons
in forms:
  * Select Icons (https://www.drupal.org/project/select_icons)
If you want to have flag icons for the language field provided by core
you need to install Language Display module, because of a bug in core:
  * Language Display (https://www.drupal.org/project/language_display)

# AUTHOR/MAINTAINER/CREDITS
-----------------------------------------------------------------------
SiliconMind, vladdancer, matsbla
Sponsored by Globalbility (https://www.drupal.org/globalbility)
