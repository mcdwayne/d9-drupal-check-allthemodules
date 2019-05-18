CONTENTS OF THIS FILE
---------------------

 * Introduction

This module integrates the colofilters.css library as a Drupal module.

 * Module Details

The module creates a formatter for the image field, using which you can select
one of the 30 styles for your image field.

The module also styles the field label as the blend mode overlay applies on the
parent div for each field. If you don't want the label you can choose to hide it
under Manage Display section of the content type OR style it differently using
css from your own theme. As this is a generic module, only the minimum needed
styles are set for the label.

 * Configuration

Add a image field on any entity, go to manage display and click the gear symbol.
Choose the image size and one of the 30 colofilters styles under Effects.

 * Troubleshooting & FAQ

- If you want to style label seprately and are getting a the image overlay as
background for your label, then you can set background attribute of your label
div to <your theme's background color>.

 * Maintainers

- Swarad Mokal - https://www.drupal.org/u/swarad07
