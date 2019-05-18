# Paragraphs Highlighter

The Paragraphs Highlighter module provides a utility to visualize nested paragraph components easily for development, demonstration, and explanation purposes. When enabled, buttons to turn on the highlighter will be displayed on the frontend of your site. You can turn on the highlighter which will create outlines around individual paragraphs targeting the entity wrapper, and you can also optionally show paragraph labels which will display the paragraph bundle.

This functionality only depends on the default entity wrapper markup, classes, and attributes. If the outer paragraph wrapper is modified or removed for a particular paragraph bundle, this functionality may not work for that specific bundle.

This module has no use on a real production environment and the functionality should never be enabled there. Configuration settings are provided to easily enforce this. Installing the module, will not add any functionality on it's own. You must also enable the highlighter by adding the config to the environment you want it enabled on:

`$config['paragraphs_highlighter.settings']['enable_highlighter'] = TRUE;`