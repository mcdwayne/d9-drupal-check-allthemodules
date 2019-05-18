# Paragraphs Report
 
The Paragraphs Report module will parse nodes of certain content types
that you check on the settings page, and make a catalog of what 
paragraphs are used on which pages.

The use case for this report is when you want to know which pages a 
specific paragraph type is used.


## Installation

1. Enable the module, go to the settings page 
Admin -> Reports -> Paragraphs Report, Settings tab.
2. Check the boxes next to the content types you want to report on. 
3. Check the "Watch for content changes..." checkbox if you want to update
the report data on node insert/update/delete. 
4. Save the form
5. Go to the Report tab and click the "Update Report Data" button


## Notes

This module stores all report data as a single JSON config array, 
and the settings form as additional variables using Drupal config table.

```$ drush cget paragraphs_report.settings```


Only the most recent version of a node is reported on, so there is no 
current focus on revisions. 


## Maintainers

Current maintainers:

- Aaron Deutsch (aisforaaron) https://www.drupal.org/u/aisforaaron
