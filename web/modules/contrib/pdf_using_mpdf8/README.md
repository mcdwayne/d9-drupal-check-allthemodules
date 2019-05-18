Introduction
------------
* Use this module to convert any HTML to PDF using the mPDF PHP Library.
* HTML <form> is supported -- use this to create editable PDF files.


Requirements
------------
* mPDF(>=7.0.0)
* Use composer to install the module's dependencies.
```
  composer require drupal/pdf_using_mpdf8:^1.0
```

Configuration
-------------
* There are several settings that can be configured in the following places:
  *admin/config/user-interface/mpdf*
  This is where all the module-specific configuration options can be set.


API
---
* In D7, pdf_using_mpdf_api() was a generic function used to generate PDF.
* In D8 however, this has been ported into a Service. To generate PDF use:
```
  /** @var \Drupal\pdf_using_mpdf\ConvertToPdfInterface $pdf */
  $pdf = \Drupal::service('pdf_using_mpdf.conversion');
  $pdf->convert($html);
```

* This Service is available to content developers that prefer to generate a
pdf file. The Service needs only one parameter, $html >> this is the
rendered HTML for a content.
