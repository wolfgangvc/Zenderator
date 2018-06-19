pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                 sh 'make build-work'
            }
        }
        stage('Push') {
            steps {
                sh 'make push-work'
            }
        }
    }
    post {
        always {
            cleanWs()
        }
    }
}