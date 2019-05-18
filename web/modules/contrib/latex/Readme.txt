---SUMMARY---

Allows you to make documents for tex. It uses a technology called Latex. LaTeX is a high-quality typesetting system. It includes features designed for the production of technical and scientific documentation. LaTeX is the de facto standard for the communication and publication of scientific documents. In other words "It can make your documents look incredible". Want to look the most professional you can, then use Latex.

This module helps you use tokens to modify your content and download a tex file which can be converted to PDF.


---REQUIREMENTS---

Latex for Drupal 8 requires the following:
------------------------------------------------------------------------------------------------

* Token
  https://www.drupal.org/project/token
  Provides a popup browser to see the available tokens for use in Latex
  fields.
* Knowledge of Tex syntax.
* Latexpdf command must be installed on your computer. 



---INSTALLATION---

Install as usual.

Place the entirety of this directory in the /modules folder of your Drupal
installation. Navigate to Administer > Extend. Check the 'Enabled' box next
to the 'Latex' and then click the 'Save Configuration' button at the bottom.


---USE---

Go to admin/config/latex/settings or Configuration > Latex > Latex form. In the Node Title field, type few characters of the desired node, select from the desired node from the dropdown menu and click on "Load" button.
This will give you the preview of tex file which you can edit according to your own need (must have knowledge of tex script).
To add tokens, click on "Browse available tokens." link provided above "Node Title" text field to browse list of all available tokens and enter the tokens of "Node" type in the preview textarea at your desired location to resolve tokens.

After compeleting the edits, click on "Submit" button to download the final tex file.


---Credits---

Latex module created by "Vipin Kumar Gupta"(https://www.drupal.org/u/viping108)

---Current Maintainers---

* Gaurav Kapoor (gaurav.kapoor)
* Prafull Ranjan (prafullsranjan)