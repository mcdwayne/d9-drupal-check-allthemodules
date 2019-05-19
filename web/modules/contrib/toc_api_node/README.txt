# TOC API Node

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Using the module
  * Author

## INTRODUCTION

This module use the TOC API module for generating a Table of content for
a whole node. The table of contents is available as an extra field and can be
placed anywhere in the node template, using display suite, twig or any module
managing node display.

The TOC options are provided by the TOC API module. You can enable per
content type the node's TOC, and for each content type select the TOC type
to use.

## REQUIREMENTS

TOC API module.

## INSTALLATION

1. Install module as usual via Drupal UI, Drush or Composer
2. Go to "Extend" and enable the TOC API Node module.

## USING THE MODULE

After you install the module go to the edit page content type on which you want
enable a TOC node (for example '/admin/structure/types/manage/article' for the
Article content type) and enable the TOC node options into the TOC node tab.

You must select the TOC type to use for this content type.

The option "Assign to content header unique ids", if checked, replace inside the
node content all the headers with headers assigned with unique ids.

To configure TOC type, go to /admin/structure/toc.

### AUTHOR

Flocon de toile
Website: https://www.flocondetoile.fr
Drupal: https://www.drupal.org/u/flocondetoile
