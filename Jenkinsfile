pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        APP_ENV = 'testing'
        COMPOSER_ALLOW_SUPERUSER = '1'
        XDEBUG_MODE = 'coverage'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Cek Lingkungan') {
            steps {
                sh '''
                    php -v
                    composer -V
                    node -v
                    # Image CI harus punya ext-gd (PhpSpreadsheet)
                    php -m | grep -i '^gd$'
                '''
            }
        }

        stage('Composer Install') {
            steps {
                sh '''
                    composer install --no-interaction --prefer-dist --no-progress
                    [ -f .env ] || cp .env.example .env
                    php artisan key:generate --force
                '''
            }
        }

        stage('Composer Audit') {
            steps {
                // gagal jika ada kerentanan
                sh 'composer audit'
            }
        }

        stage('NPM Build') {
            steps {
                sh '''
                    npm ci --ignore-scripts
                    npm run build
                '''
            }
        }

        stage('PHPUnit + Coverage') {
            steps {
                sh '''
                    vendor/bin/phpunit \
                        --coverage-clover coverage.xml \
                        --coverage-text \
                        --log-junit junit.xml | tee phpunit_output.txt
                    # gagal jika coverage kurang dari 100%
                    php scripts/check-coverage.php coverage.xml
                '''
            }
            post {
                always {
                    junit allowEmptyResults: true, testResults: 'junit.xml'
                }
            }
        }

        stage('Trivy') {
            steps {
                sh 'trivy fs --scanners vuln --severity HIGH,CRITICAL --exit-code 0 --format table --output trivy-report.txt . || true'
            }
        }

        stage('Package') {
            steps {
                // tanpa vendor/ dan node_modules/, tetap menyertakan .env.example
                sh '''
                    rm -f latest-app.zip
                    zip -rq latest-app.zip . \
                        -x 'vendor/*' 'node_modules/*' '.git/*' '.env' \
                        'coverage*' 'junit.xml' 'phpunit_*.txt' 'trivy*' '.phpunit.cache/*'
                '''
            }
        }
    }

    post {
        always {
            archiveArtifacts artifacts: 'latest-app.zip, trivy-report.txt', allowEmptyArchive: true
        }
    }
}
