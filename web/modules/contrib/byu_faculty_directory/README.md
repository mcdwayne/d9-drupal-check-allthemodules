# Table of Contents

* Introduction
* Requirements
* Installation
* Configuration
  * Modes
    * Retrieving and Providing Access to Data
      * Parent Mode
      * Child Mode
    * Displaying and Editing Data
    * Filtering the Directory View
      * Examples
    * Profile Background Image
* Work in Progress
* Maintainers

## Introduction

The BYU Faculty Directory module adds a content type and view for displaying a directory and profiles of BYU faculty members. It can also download faculty data from BYU OIT on a college level and provide the data to department websites.

## Requirements

This module requires the following modules:

* Views (<https://drupal.org/project/views>)

## Installation

Install this module as you normally would a standard Drupal 8 module See:
<https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules> for further information.

You may have to check your PHP maximum execution time (<http://php.net/manual/en/info.configuration.php#ini.max-execution-time>) as downloading large numbers of faculty members from OIT may take an extraordinarily long amount of time (~12 minutes for ~275 faculty members).

## Configuration

The BYU Faculty Directory configuration page can be found under Configuration > Content Authoring > BYU Faculty Directory Configuration.

### Modes

This module can be configured in two different 'modes':

* Parent Mode
  * Used by central college sites to retrieve faculty data from BYU OIT and provide it to departments
* Child Mode
  * Used by department sites to retrieve faculty data from the central college site

Both modes install the same View and Content Type, but the Parent retrieves data from OIT while the Child retrieves data from the Parent. This encourages department members to update data on OIT's website and on the central College site so that updated data is not hidden from others who may need access to it.

Installing the module gives access to both modes. Switching between modes is easily done in the module's configuration page.

### Retrieving and Providing Access to Data

#### Parent Mode

Parent modules retrieve data from OIT and provide it to Child modules.

To retrieve data from OIT, an API key is needed. Contact Valinda Rose, Product Manager in OIT to get an application key. Once you have the key, access the module configuration page and enter it in the 'API Key for OIT Data Retrival' textbox. Then, click 'Download all faculty data from the OIT API' and data will automatically be downloaded and created for each faculty member that your API Key allows access to.

Access from child modules is provided automatically, and can be controlled with an API key. To change this key, navigate to the module configuration page, and enter a key in the 'API Key for Child REST API' field. Then, provide this key to any departments with the child module installed that you want to have access to your faculty data.

#### Child Mode

Child modules retrieve data from Parent modules.

To retrieve data from a college site that is in Parent mode, you need to configure the module to point to the Parent module, and the Parent mode API key (see above). Once this key is obtained from the college site, navigate to the configuration page, and enter the key in the 'API Key for Parent REST API' field. Then, enter the base URL for the college site in the 'Base URL of Parent Site' field (for example, <http://et.byu.edu/>).

Once these settings are correctly configured, check the 'Download faculty data from the Parent Module' checkbox. Then, choose which type of download you want to perform - retrieve all faculty data, or just data from specific departments. If 'Specific Faculty' is chosen, enter a comma separated list of faculty members in the 'Department Names to Filter By' text box. Clicking 'Save configuration' will download and create content for the specified faculty (or all faculty) found on the Parent module site.

### Displaying and Editing Data

This module creates a block called "Faculty Directory Listing". Place this block in an appropriate region to display a directory that contains all available faculty members. Additional blocks that filter the displayed faculty members can be easily created (see Filtering below).

This module also creates a content type called "BYU Faculty Member". To add faculty members from BYU OIT's data, visit the configuration page (Home > Administration > Configuration > Content Authoring > BYU Faculty Directory Configuration). Select the appropriate checkboxes depending on if you want to download data, create content from downloaded data, or both, and click "Save Configuration".

Certain departments may want to manually edit faculty member fields (e.g. to add details about a specific course in the Courses Taught field), and this can be done by editing the faculty member's node and clicking the "[field name] or" checkbox. This will tell the module to not or this field with data from OIT in Parent mode, or from the Parent module in Child mode.

### Filtering the Directory View

The faculty member directory uses the View module, which means the directory can be filtered by field, name, number of items, and so on. Make sure to pay attention to the display selection option on any dialog boxes when changing View options - for example, make sure "This Block (or)" is selected instead of "All Displays" to apply changes only to the current display.

#### Example - Choose Number of Items To Display on a Page

1. Navigate to the BYU Faculty Directory Listing View settings
    * Structure > Views > BYU Faculty Directory Listing > Edit
2. Choose the display to edit, if not selected
3. Under Pager, click on what is currently selected next to "Use Pager" (most likely "Display All Items")
4. Select "Paged output, full pager"
5. Then, you can edit settings such as number of items on a page and pager link labels
6. Click "Apply",  and then "Save"

#### Example - Display Only Adjunct Faculty

1. Navigate to the BYU Faculty Directory Listing View settings
    * Structure > Views > BYU Faculty Directory Listing > Edit
2. Duplicate the default display
    1. To the right of the display name, click Duplicate (display name)
    2. Change the display name of the new display to Adjunct Faculty by clicking on the current display name
    3. Also change the Title and Block Name options to match the new display name
3. Add a new filter under "Filter Criteria"
    1. Click on "Title/Rank (field_byu_f_d_title)", and then Apply.
    2. Change "Is Equal To" to "Contains"
    3. Enter "Adjunct" in the Value field
    4. Click "Apply"
4. Click "Save"
5. A new block displaying only Adjunct Faculty members will now be available in Structure > Block Layout, and can be placed where desired

### Profile Background Image

Each generated profile page for faculty members has the same background/header image. This image can be changed from the default in the module configuration page. Just upload an image under 'Faculty Profile Background Image' and the changes will be applied the next time you clear the cache (Configuration > Performance > Clear all caches).

## Maintainers

* Current maintainers:
  * Sam Beckett (sbeck14) - <https://www.drupal.org/user/1405076>
  * Ben Murray (benmur64) - <>
