pipeline {
  agent any
  stages {
    stage('Pull') {
      steps {
        sh 'git pull origin 8.x-2.x --tags'
        sh 'git pull origin 8.x-2.x'
      }
    }
    stage('Push') {
      steps {
        sh 'git push git@git.drupal.org:project/byu_paragraphs.git HEAD:refs/heads/8.x-2.x'
        sh 'git push git@git.drupal.org:project/byu_paragraphs.git --tags'
      }
    }
  }
}
