---SUMMARY---

Car Specifications intends to improve adding car specification content by 
providing a select field from which you can pre-select a vehicle by 
passing year / make / model / trim , by pressing the --SHOW-- button 
the taxonomy fields will autocomplete with the car data.

This module is using the CarQuery API
For more details about the API visit this page:
http://www.carqueryapi.com/documentation/getting-started

For a full description visit project page:
https://www.drupal.org/project/car_specification

Bug reports, feature suggestions and latest developments:
http://drupal.org/project/issues/car_specification


---REQUIREMENTS---


*None. (Other than a clean Drupal 8 installation)


---INSTALLATION---

PlEASE NOTE! - You have to install the Car Specification module before 
			 Car Specification Content Type Module. Otherwise you will 
			 receive an error missing dependency!!

			 - Do not install Car Specification and Car Specification Content 
			 Type together or at the same time!

			 - The Module Car Specification Content Type will take some time
			 to install so do not panic. Just leave it to do it's thing for
			 1 min. It's adding all the fields and taxonomy.

Install as usual.

Place the entirety of this directory in the /modules folder of 
your Drupal installation. Navigate to Administer > Extend. 
Check the 'Enabled' box next to the 'Car Specification' and then 
click the 'Save Configuration' button at the bottom.

After Car Speficication Module is installed you can safely install 
Car Specification Content Type Module.

A new content type called Car Specification will be created.

For help regarding installation, visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8


---Usage---

1. Add a new content from Car Specification.
2. Choose a car from the Car Select Fields.
3. Press Show button to autocomplete all fields.

Note - The Title will be autocompleted with the year / make / model / trim 
		from Car Select.
     - To change the order of fields please go to 
     	/admin/structure/types/manage/car_specification/form-display 


---CONTACT---

Current Maintainers:
*Alexandru (lexsoft) - https://www.drupal.org/u/lexsoft

This project has been sponsored by:
*GetStudio
An innovative marketing and design studio based in the heart of London, 
specialises in web programming, brand identity and film production.
Visit: https://www.getstudio.co.uk/ for more information.
