
**Important Note**
You need to uninstall quick_edit module.  This module causes FluidUI to be
shown twice when logged in with admin bar
******************


-- SUMMARY --

This module integrates the fluidproject.org UI Options accessibility framework,
which allows visitors to customize site appearance, including font size, line
height, site contrast, generate a table of contents from the <h> tags, and
underlining and bolding links.

The module adds a "+ show display preferences" tab to the top right of the page,
which toggles the UI Options. The framework uses cookies to save user
preferences.

The module includes a precompiled framework JS file from the source but you can
compile a new file using the source here: https://github.com/fluid-project/
infusion


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/895232 for further information.

* That should be it, a note to keep fluidui library updated (might be remove from module):
1. download the latest repo from fluidui
2. run grunt custom --exclude="jQuery" --include="uiOptions"
3. Copy the infusion dir into the fluidui module at /infusion and overwrite everything currently there.


-- CONFIGURATION --

* None

-- CUSTOMIZATION --

* Style customization
  Use the module's css/fluid.css file to customize the appearance and placement
  of the "+ show display preferences" tab.

* Multilingual support
  The framework supports multilingual labels and text, but it must be created
  manually and cannot be created from the Drupal admin UI. The js/fluidui_load.js needs
  to be modified and translated files created for the TableOfContents.html file in /toc directory
  and the /messages directory. You can use drupalSettings.path.currentLanguage or
  document.location.origin to determine the URL. An example configuration
  follows:

  var langCode = drupalSettings.path.currentLanguage;

  if (langCode == "en") {
    fluid.uiOptions.prefsEditor(".flc-prefsEditor-separatedPanel", {
      tocTemplate: modulePath + "/infusion/src/components/tableOfContents/html/TableOfContents.html",
      terms: {
        templatePrefix: modulePath + "/infusion/src/framework/preferences/html",
        messagePrefix: modulePath + "/messages/en"
      }
    }
  } else if (langCode == "fr") {
    fluid.uiOptions.prefsEditor(".flc-prefsEditor-separatedPanel", {
      tocTemplate: modulePath + "/toc/fr/TableOfContents.html",
      terms: {
        templatePrefix: modulePath + "/infusion/src/framework/preferences/html",
        messagePrefix: modulePath + "/messages/fr"
      }
    }
  }