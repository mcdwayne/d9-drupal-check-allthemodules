CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Glossary
 * Configuration
 * Security
 * Maintainers


INTRODUCTION
------------

Smart Content is a toolset to enable real-time, anonymous website personalization on any Drupal 8 website. Out of the box, it allows site administrators to display different content for anonymous or authenticated users based on browser conditions.

  * For a full description of the module, visit the project page:
    https://www.drupal.org/project/smart_content

  * To submit bug reports and feature suggestions, or to track changes:
    https://www.drupal.org/project/issues/smart_content


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

  * Smart Content Segments (https://www.drupal.org/project/smart_content_segments): Adds the ability to define and use segments as conditions in Smart Content.


INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further information.

  * We recommend you install and enable Smart Content Block and Smart Content Browser, both included as sub-modules, to better experience this module's capabilities out of the box.


GLOSSARY
--------

  * Condition: A single case that can be tested and determined to be either true or false. Out of the box, with the Smart Content Browser module enabled, Language, isMobile and Operating System conditions are available.

  * Reaction: A response to a condition evaluating either true or false. Out of the box, the content administrator can select a block display when their condition evaluates true.

  * Group: Conditions can be combined and evaluated using an 'and' or 'or' operator.

  * Variation Set: A defined set of conditions and reactions.

  * Smart Block: A block, defined by the Smart Content Block sub-module, that contains any number of Variation Sets.

CONFIGURATION
-------------

Smart Content does not require any initial configuration until you are ready to begin creating and placing Smart Blocks. To configure a Smart Block, follow these steps:

  1. Navigate to the Block Layout page (admin/structure/block) and click the 'Place block' button next to the region you would like to place a Smart Block in.

  2. Find Smart Block' in the list of blocks and click the 'Place block' button.

  3. Click 'Add Variation' to create a Variation Set.

  4. Define a condition by choosing the condition you would like to evaluate from the select list in the 'Condition(s)' section and click 'Add Condition.'

  5. Complete the parameters of the condition (if/if not, equals/starts with/is empty, content to evaluate) and move on to either adding another condition or adding a reaction.

  6. To add a reaction, click the 'Add Reaction' button.

  7. Select the block that should be displayed when the previously defined conditions match and click the 'Select' button to add that block as a reaction.

  8. Define any other visibility options you require in the vertical tabs at the bottom of the block edit form.

  9. Save the block and place it in a region that is being rendered by your theme's templates.


SECURITY
--------

Smart Content is NOT intended to be used as a substitute for serverside access control. Conditions are evaluated clientside and can be viewed, changed or manipulated by someone with the right knowledge and skillset. This module is primarily focused on improving user experience and providing additional contextual conditions for displaying content, not restricting access through secure means or evaluating conditions that contain personally identifiable information.


MAINTAINERS
-----------

Current maintainers:

  * Michael Lander (michaellander); Primary Developer  - https://www.drupal.org/u/michaellander

  * Nick Switzer (switzern); Documentation, QA, Project Management -  https://www.drupal.org/u/switzern


This project has been sponsored by:

  * Elevated Third
    Empowering B2B marketing ecosystems with strategic thinking, top-notch user experience design and world-class Drupal development.