# Search API Boost Priority
This module provides boost config for additional Drupal content and 
compliments the Boost functionality provided by Search API Module. 
Site admins can configure custom boost priority for Search API Results. 
There are a number of Search API processors that allow a 
configurable boost.

## Search API Boost Processors

### Boost Priority by Role
This processor allows Roles to be assigned weights, so allows the 
possibility to boost content by "Admin" role higher than "Editor".

Example: Boost items in the following Role priority order:
* Admin
* Editor
* Member

### Boost Priority by Statistics Count
If you have a website with Statistics Module and you would like to 
sort the results totalcount. This filter allows boosting of search 
results by Statistics totalcount per item.
    
Example: Boost results in the following priority order:
* Most popular
* Least popular

### Boost Priority by File Mime
If you have a website with files and you would like to boost the 
results by file mime. This filter allows boosting of search results 
by file mime weight. 

You can also use the https://www.drupal.org/project/nice_filemime 
module to get a human friendly mime type instead of 'application/pdf'.

Example: Boost results in the following priority order:
* PDF
* Word
* JPEG

### Boost Priority by Paragraph Bundle
If you have a website with multiple Paragraph Bundles and you would 
like to boost the results by assigning arbitrary priority to each 
bundle type.

This filter allows boosting of search results by weight assigned 
to each Paragraph Bundle.

Example: Boost results in the following priority order:
* Attached File	
* Inset text
* Hero Image

### Boost priority by Content Bundle
This is part of core Search API Module already.

### Boost Priority by Media Bundle
This is part of core Search API Module already.

## Project Code

* GitHub
[search_api_boost_priority](https://github.com/dakkusingh/search_api_boost_priority)

* Drupal.org
[search_api_boost_priority](https://www.drupal.org/project/search_api_boost_priority)
