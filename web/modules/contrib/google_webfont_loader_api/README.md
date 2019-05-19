# Google Webfont Loader API (WebFont Loader)
  WebFont Loader gives you added control when using linked fonts via
  `@font-face`. It provides a common interface to loading fonts regardless of
  the source, then adds a standard set of events you may use to control the
  loading experience.

## Get Started

First enable the module, then go to:
 1)admin/build/themes/manage/google_webfont_loader_api if using Drupal 6.x
 1)admin/appearance/manage/google_webfont_loader_api if using Drupal 7
 1)Select the package that is available (choose n/a if you want to use the
   default theme font)
 1)Choose if you want to serve the Webfont Loader file locally.  While it will
   increase the size of your js file, it can get preprocessed and can be much
   faster at loading the various fonts.
 1)Press save and you're good to go!

## Creating your own font packages

Of course, the first four steps are only useful if you want to use the fonts
that come packaged with the module along with the stylesheets that were created
for demonstration purposes (which may be unlikely)

Font Packages can be treated in a similar way to a module or a theme. They can
be placed in the following directories (note that the font package gets its own
directory):
 - MY_INSTALL_PROFILE/libraries
 - sites/all/libraries
 - sites/MY_SITE/libraries
 - MY_INSTALL_PROFILE/themes
 - sites/all/themes
 - sites/MY_SITE/themes
 - MY_INSTALL_PROFILE/modules
 - sites/all/modules
 - sites/MY_SITE/modules

If you are familiar with creating .yml files for a module or a theme, then this
is the very same process.  Only now, you will be creating .font.yml files.

The .font.yml file will require:<br />
 * name - this is where you place in the name (or description) of the font
package

Optionally, you can specify:
 * render_css - this is an array which specifies a path to various css files
which implement various css rules that you may have regarding the fonts.
Please note that the path of the files are relative to the location of the
.font.yml file.

The .font.yml files currently support five libraries (we'll work our way from
easiest to hardest), and can support multiple rendering stylesheets which can be
preprocessed with the core drupal css files.

### Typekit
Your .font.yml file (let's call it typekit_demo.font.yml) would look something

name: TypeKit Demonstration
typekit:  my-typekit-id
render_css:
  - local/path/to/stylesheet1.css
  - local/path/to/stylesheet2.css

For the typekit line, you specify your typekit id along with any paths to
the rendering stylesheets.

And specify any render_css files.

### Fontdeck
Fontdeck is very similar to typekit in that it only requires an id to pull in
the font-face declarations:

name: FontDeck Demonstration
fontdeck: my-fontdeck-id
render_css:
  - http://site.com/path/to/stylesheet.css

Here, you can see that render_css can specify an external render stylesheet if
it already holds the style you are looking for.

### Google Fonts
Google font families only require you to name the various font families along
with any additional weights that you require.

name: 'Google Font Demonstration - Droid'
google_families
  - Droid Sans
  - Droid Serif
render_css
  - render_stylesheet.css

Please note that you can find a demonstration of this with the google_josefin
font provided with the module.

### Custom Fonts
Finally, webfont loader supports custom @font-face definitions.  You need to
provide the name of the font-face families that need to be detected along with
a path to the css file(s) where there definitions will be found

name: Custom Font Demonstration - Beteckna
custom_families:
  - BetecknaLowerCaseBold
  - BetecknaLowerCaseItalic
  - BetecknaLowerCaseMedium
custom_style_css:
  - path/to/definition_stylesheet.css
  - path/to/definition_stylesheet2.css
render_css:
  - path/to/render_stylesheet.css

Please note that custom_style_css can also refer to just one file (and is
recommended) if you are serving a font that is hosted on your own server.

You can find out a lot more about the web font loader and how to format your css
at [gfontapi]

[gfontapi]: https://code.google.com/apis/webfonts/docs/webfont_loader.html
