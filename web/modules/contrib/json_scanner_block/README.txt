Json Scanner Block

__________________

Setting:

1. After installing this module you will get a Config link on module page at Json Scanner Module.
2. Using the config form where you have to enter all the json url details. (Name and JSON url are require field)
E.g - Fill in name - Test Json First, Fill in json url - https://jsonplaceholder.typicode.com/posts
(auth will not work in current release, it will be for future, for those urls where authentication required to access json)


__________________

Use:

Now after addition/settings goto any template and dump variable to check the array to print:

{{dump(given_variable_on_listing_page_after_creation)}} 
