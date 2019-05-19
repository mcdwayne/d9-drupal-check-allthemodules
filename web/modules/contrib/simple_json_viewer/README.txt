Simple Json Viewer




Simple JSON Viewer is the module built to beautify the given JSON.

Features:

It can be used with any editor by using simple class "simple-json-viewer"
it can be used in custom js by using simpleJsonViewer function
it can be used as ajax by using simpleJsonViewer function


Usage 1:

Just add some JSON in any html tag with the class name "simple-json-viewer"

<p class="simple-json-viewer">{ "name": "JsonViewer", "author": { "name": "Karthikeyan Manivasagam", "email": "karthikeyanm.inbox@gmail.com" ,"contact": [ { "location": "office" ,"number": 7358196062 } ,{ "location": "home" ,"number": 7358196062 } ] } }</p> 


Usage 2:

Define your own custom id or class with empty html tag and then call  simpleJsonViewer function with  json Obj

<div id="custom-id"></div>


    var json = { "name": "JsonViewer", "author": { "name": "Karthikeyan Manivasagam", "email": "karthikeyanm.inbox@gmail.com" ,"contact": [ { "location": "office" ,"number": 7358196062 } ,{ "location": "home" ,"number": 7358196062 } ] } };

 $('#custom-id').simpleJsonViewer(json);
