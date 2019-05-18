# Installation instructions

## Installation using composer

This module is best installed with composer.

Run `composer require drupal/leaflet_countries` to install the module.

Add the following to your composer.json in the "repositories" section:
```
{
    "_comment": "The countries geoJSON files as a library",
    "type": "package",
    "package": {
        "name": "mledoze/countries",
        "version": "dev-master",
        "type": "drupal-library",
        "dist": {
            "url": "https://github.com/mledoze/countries/archive/master.zip",
            "type": "zip"
        }
    }
}
```
Then run `composer require mledoze/countries` to install the module and dependencies.

## Installation using drush

Download the modules using drush:

```
drush dl leaflet_countries leaflet leaflet_topojson
```

Download the countries library from [https://github.com/mledoze/countries/archives/master.zip](https://github.com/mledoze/countries/archives/master.zip)

Place the unzipped library in /libraries in your webroot.

## Manual installation

Download the following modules:

* leaflet
* leaflet_countries
* leaflet_topojson

Place them in your modules directory.

Download the countries library from [https://github.com/mledoze/countries/archives/master.zip](https://github.com/mledoze/countries/archives/master.zip)

Place the unzipped library in /libraries in your webroot.

# Usage

After enabling the module you need to add a field to the content type you want to use to represent a country. The field to add is labeled 'Country (leaflet map)'.

In your content type's 'Manage display' settings you may choose to output the field as a 3 letter country code or more usefully as a rendered country map by choosing the appropriate format.

Create a piece of content and associate it using the new field to a country.

View your new node and you should see the field output either as a map or a three letter country code.

## Views integration

Add a new view based on 'content' and create a page or a block display in the view. In the 'Format' section choose 'Leaflet'.

Add a 'Leaflet Attachment' display and in the 'Format' section choose Format: Countries and Show: Country outline.

Now add your previously created field to this display and then edit the row style settings and ensure 'Data Source' is associated with your field. You can also optionally configure settings in here.
 
In the 'Attachment Settings' section of the display you need to associate this display with the previously created block or page display in the 'Attach to' section.

Save your view and view it and you should see your node(s) represented as country outlines with a fill on the map.
