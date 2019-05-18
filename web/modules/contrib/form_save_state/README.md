# Overview

This module provides a way to autosave data entered in any Drupal form without actually submitting the form, which helps if the user is writing an article or a comment and the browser crashed or the power went down, or even if the window was closed accidently. Supported fields: checkbox, select, input[type=text], textarea, ckeditor.

It works using the [jQuery Sisyphus plugin](http://sisyphus-js.herokuapp.com/), which is a lightweight jQuery plugin that uses Local Storage to save form fields every specific time span that is configurable from the module settings page.

Supported fields: checkbox, select, input[type=text], textarea, ckeditor

# ROADMAP

* Manage external js libraries with drupal library api. Remove them from this module
* Implement time interval for form state save. 
* Remove ckeditor from dependencies.
* Add support for different wysiwyg editors 