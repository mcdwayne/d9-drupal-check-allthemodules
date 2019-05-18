# Developer Notes

* You might want to double check the formatting of the fields:
  * This is found in BYUFacultyDirectoryForm::createSingleFacultyMember()
  * I've tried to parse through the XML so as to make things look pretty when it's displayed on the profile/listing page, but some tweaks probably need to be made

* I added a link to the CV and education info to the node--byu_faculty_member.html.twig template, but you'll need to update the component/CDN to add the cv and education slots.

* Ask Shawn Ward for the CAEDM/Engineering College OIT API key as well as information on connecting to the decoysam-ct.et.byu.edu proxy server.

* To do list:
  * Verify and finalize faculty member fields
  * Automation of Data Retrieval (from OIT and parent module)
  * Sanitize parent base URL input (add <http://> if needed, trailing slash, etc.)

* Feel free to ask any questions and I'll get back to you as soon as I can - sbeck14@me.com
