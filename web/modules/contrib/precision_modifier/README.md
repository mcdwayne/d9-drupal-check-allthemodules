#Precision Modifier
This module allows site builders <b>change</b> precision and scale values of node fields of type decimal that have
data associated with them.

####Usage

Once the module has been downloaded and enabled, the following steps are required in order to increment the 
precision and scale of a field of type decimal:
- select the manage fields operation of the desired content type 
- select the edit operation for the desired decimal field with data associated to it. 
- select the 'Field Settings' tab for the selected field. (The option to convert precision and scale for the field is 
made available if the decimal field selected has data associated with it.)
- check the checkbox (Enable precision increment)
- from the available drop downs select the desired precision and scale
- save the field settings  

<u>Note</u>: <br>
1. As you change the precision and scale of a field that has data associated to it, be aware it can cause some loss of 
precision.
2. Precision is the total number of significant digits in the number and scale is the total number of digits to the right 
of the decimal point. For example, 123.4567 has a precision of 7 and a scale of 4. More information can be found 
<a href='https://www.drupal.org/docs/7/api/schema-api/data-types/decimal-numeric'>here</a>