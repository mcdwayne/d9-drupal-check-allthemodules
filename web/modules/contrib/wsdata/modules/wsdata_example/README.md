This module provides some example configurations for the wsserver and wscall. 
There is also an example WSDecoder plugin to demonstrate formating the data in a particular way once received. 

We are using this site http://jsonplaceholder.typicode.com/ for a Example server. 

# Example 1: WSFields 

There is a content type Web Service Example that should have 4 fields associated to it. 
3 of those fields are wsfields, creating a Web Service Example node will require you to put in a single post ID.

On the view page of that node you should see the associated title, body and userID of that post.

# Example 2: WSBlock

On the stucture block page, if you place a "Wsdata Block" in a region.
If you select the "Fetch All Post" for the Web Service Call it should display all of the post in a formatted list. 


