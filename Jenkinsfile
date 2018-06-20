pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                 sh 'make build'
            }
        }
        stage('Push') {
            steps {
                sh 'make push'
            }
        }
    }
    post {
        always {
            cleanWs()
        }
    }
}