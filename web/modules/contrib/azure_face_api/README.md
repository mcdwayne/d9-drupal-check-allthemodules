# Azure Cognitive Services API

Microsoft Azure Cognitive Services exposes machine learning APIs and enables developers to easily integrate  intelligent features - such as emotion and video detection; facial, speech and vision recognition; and speech and language understanding - into their Drupal applications.

## [Face API Module](https://azure.microsoft.com/en-us/services/cognitive-services/face/)
Face API Module integrates with Microsoft Face API, a cloud-based service that provides the most advanced face algorithms. Face API has two main functions: face detection with attributes and face recognition.

![Face - Detect](https://docs.microsoft.com/en-us/azure/cognitive-services/face/images/face.detection.jpg)

* Detect human faces and compare similar ones
* Organize images into groups based on similarity
* Identify previously tagged people in images

Following API methods are available in the current release.

### [Face - Detect](https://westus.dev.cognitive.microsoft.com/docs/services/563879b61984550e40cbbe8d/operations/563879b61984550f30395236)
Face API detects up to 64 human faces with high precision face location in an image. And the image can be specified by file in bytes or valid URL.

![Face - Detect](https://www.drupal.org/files/Screen%20Shot%202017-09-15%20at%2010.53.04%20am.png)


## [Emotion Recognition API Module](https://docs.microsoft.com/en-us/azure/cognitive-services/emotion/home)

The Emotion API beta takes an image as an input, and returns the confidence across a set of emotions for each face in the image, as well as bounding box for the face, from the Face API.

![Emotion Recognition API](https://www.drupal.org/files/Screen%20Shot%202017-09-15%20at%2010.41.43%20am.png)

Following API methods are available in the current release.

### [Recognition](https://westus.dev.cognitive.microsoft.com/docs/services/5639d931ca73072154c1ce89/operations/563b31ea778daf121cc3a5fa)
Recognises the emotions expressed by one or more people in an image, as well as returns a bounding box for the face. The emotions detected are happiness, sadness, surprise, anger, fear, contempt, and disgust or neutral.

![Emotion Recognition](https://www.drupal.org/files/Screen%20Shot%202017-09-15%20at%2010.54.48%20am.png)

## [Computer Vision API Module](https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/)
Extract rich information from images to categorize and process visual data – and machine-assisted moderation of images to help curate your services.

![Computer Vision API](https://www.drupal.org/files/Screen%20Shot%202017-09-15%20at%2010.47.01%20am.png)

Following API methods are available in the current release.
### [Image Analysis](https://westcentralus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1fa)
This feature returns information about visual content found in an image. Use tagging, descriptions, and domain-specific models to identify content and label it with confidence. Apply the adult/racy settings to enable automated restriction of adult content. Identify image types and color schemes in pictures.
