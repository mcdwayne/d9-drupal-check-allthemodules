# About RegEx Field Validation

_This module provides an extra validation option for text fields_

_It uses the power of regular expressions to validate the content from fields_

How to use it?

Go to Manage Fields on any Content type
Edit any text field settings
Locate the RegEx Field Validation box that should be available in the 
settings form
Check the Validate field with RegEx option to activate the Regular 
Expression and Error 
message fields
Type in the regular expression that you want the field value to be 
matched against
Type in the error message that you want to be displayed when the field 
value does 
not validate
Examples of regular expressions that can be used

* `\^\d.\d+\Z\`
Decimal followed by dot and unlimited decimals (1.12, 1.55, etc.

* `\^[^<\x09]{0,100}\Z\`
Text containing between 0 and 100 characters.

* `\^[AaBb]\Z\`
1 character either A, a, B or b.

* `\^(http|https):\/\/.{2,80}\Z\`
URL starting with “http” or “https” and contains between 2 and 80 characters.

* `\^.{2,40}\@.{2,50}\..{2,5}\Z\`
Email address containing between 2 and 40 characters before the “@”, then between 2 and 50 characters as the domain name and between 2 and 5 as the top level domain

* `\^(0[1-9]|1[0-2])\Z\`
2 digits that could represent the day of the month (01-31)

* `\^(19|20|21)[0-9]{2}\Z\`
4 digits that could represent the year (1900-2199)

* `\^(ABC|DEF|GHI|JKL|MNO|PQR|STU|VWX)?\Z\`
Accept a tree letter string that can be found in that list

* `\^([0-9]+(\.[0-9]{2})?)?\Z<\`
Numeric with "." as decimal separator (29.99)

* `\^[0-9.]{1,8}\Z<\`
Numerical value between 1 and 8 digits.

* `\^[^<\x09\x0a\x0d]{0,10}\Z\`
Single line between 0 and 10 characters that should not contain HTML markup

* `\^[^<]{0,100}\Z\`
Multiple lines between 0 and 100 characters that should not contain HTML markup

* `\^[^<\x09\x0a\x0d]{0,1000}\Z\`
Text containing between 0 and 1000 letters, numbers and spaces



Maintainer

* Ionut Stan (https://www.drupal.org/u/ionutstan)

Supporting organization

* Softescu SRL (https://www.drupal.org/softescu-srl)
