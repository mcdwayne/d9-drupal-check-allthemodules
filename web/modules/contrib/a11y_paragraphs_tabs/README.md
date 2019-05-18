
CONTENTS OF THIS FILE
--------------------------------------------------------------------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
--------------------------------------------------------------------------------

A11Y Paragraphs Tabs gives the user the ability to easily add tabs via
paragraphs to their content that complies to Accessibility (A11Y) standards
and on mobile the tabs changes to an accordion.

This module creates 3 new paragraphs namely:
- A11Y Paragraphs Tabs Wrapper
- A11Y Paragraphs Tabs Panel
- A11Y Paragraphs Tabs Content

The wrapper (A11Y Paragraphs Tabs Wrapper) contains the tab panel
(A11Y Paragraphs Tabs Panel) of which you can add as many tabs as you need.
In turn, the tabs panel (A11Y Paragraphs Tabs Panel) contains a paragraph in
which you can add the paragraphs you would like to use inside the tab panel.

A11Y Paragraphs Tabs uses Matthias Ott's A11Y Accordion Tabs js:
https://github.com/matthiasott/a11y-accordion-tabs

FEATURES
--------------------------------------------------------------------------------
o Tabs that comply to Accessability (a11y) standards.
o Tabs become an accordion on mobile.

REQUIREMENTS
--------------------------------------------------------------------------------
- Paragraphs: https://www.drupal.org/project/paragraphs
- Paragraphs have a dependancy on Entity Reference:
  https://www.drupal.org/project/entity_reference_revisions
- A11Y Accordion Tabs js library:
  o Download A11Y Accordion Tabs js library here:
    https://github.com/matthiasott/a11y-accordion-tabs
  o Extract and rename it to "a11y-accordion-tabs", so the
    assets are at:
    /libraries/a11y-accordion-tabs/a11y-accordion-tabs.js

INSTALLATION
--------------------------------------------------------------------------------

Installation By Downloading Module:
- Install the module as per usual way under "Extend"
- Verify installation by visiting /admin/structure/paragraphs_type and seeing
your new Paragraphs: A11Y Paragraphs Tabs Wrapper, A11Y Paragraphs Tabs Panel,
A11Y Paragraphs Tab Content.
- Download "A11Y Accordion Tabs" js from
https://github.com/matthiasott/a11y-accordion-tabs
- Extract download and move to your /libraries folder.
- Rename folder to "a11y-accordion-tabs" and make sure you have the correct
path to the js file: /libraries/a11y-accordion-tabs/a11y-accordion-tabs.js
- DONE

Installation with Composer
- If you are using composer on your Drupal environment, run these 2 commands and
that should add it to your composer file:
  o composer require drupal/paragraphs
  o composer require npm-asset/a11y-accordion-tabs:^0.5.0
- And then you can run "composer install" to download the packages


CONFIGURATION
--------------------------------------------------------------------------------

- Go to your content type and add a new field of type Reference revisions,
Paragraphs.
- On the field edit screen, you can add a drescription, and choose which
paragraphs you want to allow for this field. Check only
"A11Y Paragraphs Tabs Wrapper". This will add everything you need.
Click Save Settings.
- Adjust your form display, placing the field where you want it.
- Add the field into the Manage display tab.
- Done. You can now add tabs to your content.


MAINTAINERS
--------------------------------------------------------------------------------
A11Y Paragraphs Tabs - Hennie Martens
