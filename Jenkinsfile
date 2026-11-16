pipeline {
    agent any

    options {
        skipDefaultCheckout()
        disableConcurrentBuilds()
        timeout(time: 30, unit: 'MINUTES')
    }

    parameters {
        choice(name: 'TARGET', choices: ['staging', 'production'], description: 'Lingkungan deploy Laparin')
    }

    triggers {
        githubPush()
    }

    stages {
        stage('Checkout') {
            steps {
                script {
                    if (isUnix()) {
                        error('Pipeline Laparin ini memerlukan agent Windows dengan Docker Desktop.')
                    }

                    def revision = checkout scm
                    env.LAPARIN_TAG = revision.GIT_COMMIT.take(12)
                    env.LAPARIN_TARGET = params.TARGET ?: 'staging'
                    env.LAPARIN_TEST_IMAGE = "laparin-ci-test:${env.LAPARIN_TAG}-${env.BUILD_NUMBER}"
                }
                powershell './jenkins/pipeline.ps1 -Action Check'
            }
        }

        stage('Test') {
            steps {
                powershell './jenkins/pipeline.ps1 -Action Test'
            }
        }

        stage('Build Image') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'laparin-dockerhub',
                    usernameVariable: 'DOCKERHUB_USER',
                    passwordVariable: 'DOCKERHUB_PAT'
                )]) {
                    powershell './jenkins/pipeline.ps1 -Action Build'
                }
            }
        }

        stage('Push Image') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'laparin-dockerhub',
                    usernameVariable: 'DOCKERHUB_USER',
                    passwordVariable: 'DOCKERHUB_PAT'
                )]) {
                    powershell './jenkins/pipeline.ps1 -Action Push'
                }
            }
        }

        stage('Deploy') {
            steps {
                withCredentials([
                    usernamePassword(
                        credentialsId: 'laparin-dockerhub',
                        usernameVariable: 'DOCKERHUB_USER',
                        passwordVariable: 'DOCKERHUB_PAT'
                    ),
                    file(credentialsId: "laparin-env-${env.LAPARIN_TARGET}", variable: 'LAPARIN_ENV_FILE')
                ]) {
                    powershell './jenkins/pipeline.ps1 -Action Deploy'
                }
            }
        }

        stage('Smoke Test') {
            steps {
                powershell './jenkins/pipeline.ps1 -Action Smoke'
            }
        }
    }

    post {
        success {
            echo "Deploy Laparin berhasil untuk ${env.LAPARIN_TARGET} pada commit ${env.LAPARIN_TAG}."
        }
        failure {
            echo 'Pipeline Laparin gagal. Periksa stage yang gagal dan console log.'
        }
    }
}
