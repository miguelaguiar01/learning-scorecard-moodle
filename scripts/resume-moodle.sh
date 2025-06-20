#!/bin/bash
set -e

# Configuration
GITHUB_USERNAME="miguelaguiar01"
WEB_CONTAINER="moodle-moodle_web-1"
DB_CONTAINER="moodle-moodle_db-1"
WEB_IMAGE="ghcr.io/$GITHUB_USERNAME/moodle_web"
DB_IMAGE="ghcr.io/$GITHUB_USERNAME/moodle_db"

echo "📥 Resuming Moodle containers from GitHub Container Registry..."

# Function to safely remove container if it exists
remove_container_if_exists() {
    local container_name=$1
    if docker ps -a -q -f name="^${container_name}$" | grep -q .; then
        echo "🗑️  Removing existing container: $container_name"
        docker rm -f $container_name
    fi
}

# Remove existing containers
remove_container_if_exists $WEB_CONTAINER
remove_container_if_exists $DB_CONTAINER

# Pull latest images
echo "⬇️  Pulling latest web image..."
docker pull $WEB_IMAGE:latest

echo "⬇️  Pulling latest database image..."
docker pull $DB_IMAGE:latest

# Start database container first (web depends on it)
echo "🚀 Starting database container..."
docker run -d --name $DB_CONTAINER $DB_IMAGE:latest

# Wait a moment for database to initialize
echo "⏳ Waiting for database to initialize..."
sleep 5

# Start web container
echo "🚀 Starting web container..."
docker run -d --name $WEB_CONTAINER --link $DB_CONTAINER:db $WEB_IMAGE:latest

echo "✅ Moodle containers resumed successfully!"
echo "🌐 Web Container: $WEB_CONTAINER"
echo "🗄️  Database Container: $DB_CONTAINER"
echo ""
echo "📋 Check status with: docker ps"