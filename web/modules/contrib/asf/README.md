#Installation
-------------

Add to your composer.json:
"drupal/asf": "~1.2",
Run the command "composer update".

Only this is needed for the module to work.
The interface is nicer with the following library:

```javascript
"xdan/datetimepicker": "*",
```

```javascript
	"repositories": {
		"datetimepicker": {
			"type": "package",
			"package": {
				"name": "xdan/datetimepicker",
				"version": "2.5.4",
				"type": "drupal-library",
				"dist": {
					"url": "https://github.com/xdan/datetimepicker/archive/master.zip",
					"type": "zip"
				},
				"source": {
					"url": "https://github.com/xdan/datetimepicker.git",
					"type": "git",
					"reference": "v2.5.4"
				}
			}
		},
    }
```
    
#QUICK START GUIDE
-----------------
Enable the module.

Add a advanced shedule field to your custom content type.

make sure your cron runs with the wished granularity.
(if you want to be able to publish any minute, run cron each minute).
use an advanced cron module for that. e.g. http://drupal.org/project/elysia_cron

Create a node , and select the publication
  start date/ end date / start time / end time
Select if the pubvlication is iterative
Iterative means the node is published on start hr
 each day and depublished on end hr eacht day, until a end is reached.
Iteration count or end date can cause to end the publication scheme.
