#ATTRIBUTIONS DEMO README.md

##CONTENTS OF THIS FILE
  
* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Troubleshooting
* Maintainers

## INTRODUCTION

The **Attributions Demo** module demonstrates how third part materials
in a Drupal module or theme may be documented in the project's
.info-file.

If you also enable the **Attributions** module, any registered
attributions that are not hidden may be rendered on an attributions
page or in an attributions block.

The reason **Attributions** is not set as an requirement, is that one
use case is to provide attributions (in the module's .info-file),
while the rendering of required attributions is done by other means.

You should not have **Attributions Demo** enabled on a production
site, since it displays bogus attributions.


## REQUIREMENTS

None.


## RECOMMENDED MODULES

* [Advanced Help][1]:<br>
  When this module is enabled, display of the project's `README.md`
  will be rendered when you visit `help/attributions/README.md`.
* [Markdown filter][2]:<br>
  When this module is enabled, display of the project's `README.md`
  will be rendered with the markdown filter.
* [Attributions][3]:<br>
  When this module is enabled, the attributions registered by this
  demo module will be rendered on a page (path `attributions`) or in
  a  block (named `Attributions block`).

## INSTALLATION

1. Install as you would normally install a contributed drupal
   module. See: [Installing modules][4] for further information.

## CONFIGURATION

The module has no configurable elements.

## TROUBLESHOOTING

Please note that the attributions defined in this project's .info-file
will not appear until you also enable the **Attributions** module.

## MAINTAINERS

* [gisle](https://www.drupal.org/u/gisle) (current maintainer)

Any help with development (patches, reviews, comments) are welcome.


[1]: https://www.drupal.org/project/advanced_help
[2]: https://www.drupal.org/project/markdown
[3]: https://www.drupal.org/project/attributions
[4]: https://drupal.org/documentation/install/modules-themes/modules-7
