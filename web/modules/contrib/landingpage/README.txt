README.txt
==========

LandingPage Framework is a set of modules and themes that allow to create 
beautiful LandingPages inside you Drupal 8 site.

You can set a separate theme for each LandingPage into one Drupal site!

STUFF OF THE PACKAGE
--------------------

LandingPage - a root module that provide LandingPage content type and define 
				Theme, Display, Classes and Archor fields.

LandingPage paragraph modules include different paragraph types:

- LandingPage ContactForm & TextArea
- LandingPage DonateBTC & TextArea
- Landingpage GMap & TextArea
- Landingpage Hero Image & CountDown
- Landingpage Hero Image & TextArea
- Landingpage Image & TextArea
- Landingpage Image & TextField
- LandingPage TextField & Images
- Landingpage Textfield & Links
- Landingpage TextField & TextArea
- LandingPage Video & TextArea

Landingpage Clone - provide an additional tab with LandingPage clone form. 
				This module can be very useful if you want to start your Landing page
				with fake Example LandingPage. You should turn on an Example module,
				clone the fake LandingPage, make your changes and turn off an Example Module.

LandingPage Example modules:
- LandingPage CaseStudy Examples (generate 2 pages)
- LandingPage CV Example
- LandingPage Event Example

Landingpage Export (experimental) - provide an ability to export Landing Page content and settings stuff in YAML format.
				You can use this file and archive with the images to create your module for quick generation of your
				Landing page on the separate Drupal 8 environment. I'm going to simplify and improve export/import process 
				in the future. So this feature is still in the development but you can test and use it!

Themes:

LandingPage Bootstrap - a base theme (based on Bootstrap theme) that include paragraph templates and
				styles of predefined classes.

3 themes for fake Examples:
- LandingPage CaseStudy
- LandingPage CV
- LandingPage Event

landingpage_starterkit - the template for your custom LandingPage theme. You need to copy this folder in /themes
				and follow instructions in README.txt in the folder.

DEPENDENCIES
------------

- Bootstrap theme (https://www.drupal.org/project/bootstrap)
- Paragraphs module (https://www.drupal.org/project/paragraphs)
- Entity Reference Revisions module (https://www.drupal.org/project/entity_reference_revisions)

Some LandingPage sub-modules have dependencies:
- video_embed_field (LandingPage Video & TextArea)
- google_map_field (Landingpage GMap & TextArea)
- field_timer (Landingpage Hero Image & CountDown)

QUICK START
-----------

Turn on LandingPage Clone module and after that turn on one of the LandingPage Example modules

- LandingPage Event Example
- LandingPage CV Example
- LandingPage CaseStudy Example

After that clone the Fake Example LandingPage and customise cloned page as you wish.
Uninstall LandingPage Example module.

THREE LEVELS OF LANDINGPAGE CUSTOMISATION
-----------------------------------------

1st level:
	
	In admin area you can select on of the 4 LandingPage themes.
	
	For each paragraph type you can select View Mode (Display) type (Default, Left, Right)
	
	Please input Name of LandingPage Class (autocomplete). The full list of available classes you can find on /admin/structure/landingpage_skin. Also you can set CSS properties for each paragraph, one per line, for example, if you input "background-color: #f00;" the background of that paragraph will be red. You can use colorpicker in the right to specify the color. It works for all inline styles with colors startes with hash (#), otherwise colorpicker values will be ignored. Manipulation with LandingPage classes is recommended, it cause more predictable results, and you can customise each paragraph view on the base of LandingPage classes directly into you theme.

	You can set an anchor link for each separate paragraph on your LandingPage and navigate inside your LandingPage.

2nd level:

	Use 'landingpage_starterkit' template to start and customize your own LandingPage theme. See instructions inside the folder.
	You can add set of your classes in the .info.yml file of your theme and they will be supported in LandingPage Classes autocomplete.

3rd level:

	You can create new predefined classes on /admin/structure/landingpage_skin and new paragraphs on /admin/structure/paragraphs_type. Please use 'landingpage_' to make your new paragraph automatically attached to LandingPage content type. Don't forget to add support your new classes in CSS of your theme and support of new paragraph types in templates of your theme!

SUPPORT
-------

Please feel free to post an issue on https://www.drupal.org/node/add/project-issue/landingpage
with bug report or feature request.

If you have questions, ideas or projects that can be done on LandingPage Framework you can mail me to 'vaso1977@gmail.com' or Skype me to 'vasilyyaremchuk'.

You can find the demo and the Donate form on http://landingpage.abzats.com/
