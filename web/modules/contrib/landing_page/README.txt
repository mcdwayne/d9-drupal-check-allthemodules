SUMMARY
--------

Landing pages module will let you to select the templates of your
own for a node. It allows to turn off the default css, js provided.
It has an option to include custom js and css. Lets us include our
own template files for each page.

INSTALLATION
-------------

  1. Download, unzip the module and copy it to contrib folder under modules.
  2. Go to /admin/modules and enable the module.

CONFIGURATION
--------------

Feature 1. Selecting theme for a node:

  1. Scan the new template files added,
     There are 2 options to scan newly added template files,
      1.1. Put the .html.twig files to anywhere within the installation or
           else to the current theme's template folder.
      1.2. At admin/config/content/landing-pages,
        1.2.1. The .html.twig files is within the current theme's template
               folder, and landing page template files will be scanned by
               default.
        1.2.2. Or else enter the relative path, see the help text.
      1.3. Later scan with the button.
      1.4. On adding a new template twig file, make sure to re-scan,
           otherwise you won't take an effect.

  2. Content type landing page at /node/add/landing_page
      2.1. See the template files scanned in the drop down.
      2.2. Turn off the default css and js using check boxes.
      2.3. Include custom css and js for CSS, Header JS and Footer JS fields.
      2.4. For CSS, Header JS and Footer JS fields, select Restricted
           HTML as text format.
      2.5. For body, select Full HTML as text format.
      2.6. Save the node.
