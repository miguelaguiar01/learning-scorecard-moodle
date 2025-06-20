#!/bin/bash
set -e

echo "🔥 CREATING COMPLETE MOODLE IMAGES WITH DATA"
echo "================================================"

# Create project directory
mkdir -p ~/moodle-complete-build
cd ~/moodle-complete-build

# Copy your actual data from running containers
echo "📋 Copying data from running containers..."

# Create data directories
mkdir -p moodle_data
mkdir -p mysql_data
mkdir -p learning_scorecard

# Copy Moodle files from running container (excluding learning-scorecard)
echo "📁 Copying Moodle files..."
docker cp moodle-moodle_web-1:/bitnami/moodle/. ./moodle_data/

# Remove learning-scorecard from copied data (we'll use bind mount instead)
rm -rf ./moodle_data/local/learning_scorecard

# We don't copy learning_scorecard here - it will be a bind mount

# Copy database files from running container
echo "🗄️  Copying database files..."
docker cp moodle-moodle_db-1:/var/lib/mysql/. ./mysql_data/

# Create Dockerfile for web
cat > Dockerfile.web << 'EOF'
FROM bitnami/moodle:4.1

# Copy your existing Moodle files and data (but NOT learning-scorecard)
COPY ./moodle_data/ /bitnami/moodle/

# Create the learning-scorecard directory but leave it empty for bind mount
RUN mkdir -p /bitnami/moodle/local/learning_scorecard

# Set proper permissions
USER root
RUN chown -R 1001:1001 /bitnami/moodle/
RUN chmod -R 755 /bitnami/moodle/

# Switch back to bitnami user
USER 1001

# Expose ports
EXPOSE 8080 8443
EOF

# Create Dockerfile for database
cat > Dockerfile.db << 'EOF'
FROM mariadb:10.6

# Copy your existing database data
COPY ./mysql_data/ /var/lib/mysql/

# Set proper permissions
RUN chown -R mysql:mysql /var/lib/mysql/
RUN chmod -R 755 /var/lib/mysql/

# Set environment variables
ENV MYSQL_USER=moodle
ENV MYSQL_PASSWORD=moodle
ENV MYSQL_ROOT_PASSWORD=root
ENV MYSQL_DATABASE=moodle

# Expose port
EXPOSE 3306
EOF

# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  moodle_db:
    build:
      context: .
      dockerfile: Dockerfile.db
    container_name: moodle-db-complete
    environment:
      - MYSQL_USER=moodle
      - MYSQL_PASSWORD=moodle
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_DATABASE=moodle
    ports:
      - "3306:3306"
    networks:
      - moodle_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "moodle", "-pmoodle"]
      interval: 30s
      timeout: 10s
      retries: 5

  moodle_web:
    build:
      context: .
      dockerfile: Dockerfile.web
    container_name: moodle-web-complete
    environment:
      - MOODLE_DATABASE_HOST=moodle_db
      - MOODLE_DATABASE_NAME=moodle
      - MOODLE_DATABASE_USER=moodle
      - MOODLE_DATABASE_PASSWORD=moodle
      - MOODLE_DATABASE_PORT_NUMBER=3306
      - ALLOW_EMPTY_PASSWORD=yes
    ports:
      - "8080:8080"
      - "8443:8443"
    volumes:
      # Keep your git repo as a live bind mount
      - ./learning-scorecard-moodle:/bitnami/moodle/local/learning_scorecard
    depends_on:
      moodle_db:
        condition: service_healthy
    networks:
      - moodle_network

networks:
  moodle_network:
    driver: bridge
EOF

# Build and tag the images
echo "🏗️  Building complete images with your data..."
docker build -f Dockerfile.db -t ghcr.io/miguelaguiar01/moodle_db_complete:latest .
docker build -f Dockerfile.web -t ghcr.io/miguelaguiar01/moodle_web_complete:latest .

# Push to GitHub Container Registry
echo "☁️  Pushing to GitHub Container Registry..."
docker push ghcr.io/miguelaguiar01/moodle_db_complete:latest
docker push ghcr.io/miguelaguiar01/moodle_web_complete:latest

echo "✅ SUCCESS! Your complete Moodle images are now available!"
echo "📦 Images pushed:"
echo "   - ghcr.io/miguelaguiar01/moodle_db_complete:latest"
echo "   - ghcr.io/miguelaguiar01/moodle_web_complete:latest"
echo ""
echo "🚀 You can now run the resume script on your new machine!"
