# Azure Text Analytics API

Text Analytics API is a cloud-based service that provides advanced natural language processing over raw text, and includes three main functions: sentiment analysis, key phrase extraction, and language detection.

## [Sentiment Analysis](https://westus.dev.cognitive.microsoft.com/docs/services/TextAnalytics.V2.0/operations/56f30ceeeda5650db055a3c9)

![Sentiment Analysis](https://www.drupal.org/files/sentiment-analysis.png)

Find out what customers think of your brand or topic by analyzing raw text for clues about positive or negative sentiment. 

The API returns a numeric score between 0 and 1. Scores close to 1 indicate positive sentiment, and scores close to 0 indicate negative sentiment. Sentiment score is generated using classification techniques. The input features of the classifier include n-grams, features generated from part-of-speech tags, and word embeddings.


## [Key Phrase Extraction](https://westus.dev.cognitive.microsoft.com/docs/services/TextAnalytics.V2.0/operations/56f30ceeeda5650db055a3c6)

![Key Phrase Extraction](https://www.drupal.org/files/key-phase-extraction.png)

Automatically extract key phrases to quickly identify the main points. For example, for the input text ‘The food was delicious and there were wonderful staff’, the API returns the main talking points: ‘food’ and ‘wonderful staff’.

## Supported Languages
- Danish
- Dutch
- English
- Finnish
- French
- German
- Greek
- Italian
- Japanese
- Norwegian
- Polish
- Portuguese (Portugal)
- Portuguese (Brazil)
- Russian
- Spanish
- Swedish
- Turkish
