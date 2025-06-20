#!/bin/bash
set -e  # Exit on any error

# Configuration
GITHUB_USERNAME="miguelaguiar01"
WEB_CONTAINER="moodle-moodle_web-1"
DB_CONTAINER="moodle-moodle_db-1"
WEB_IMAGE="ghcr.io/$GITHUB_USERNAME/moodle_web"
DB_IMAGE="ghcr.io/$GITHUB_USERNAME/moodle_db"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

echo "🔄 Saving Moodle containers..."

# Function to check if container exists and is running
check_container() {
    local container_name=$1
    if ! docker ps -q -f name="^${container_name}$" | grep -q .; then
        echo "❌ Container '$container_name' is not running"
        return 1
    fi
    return 0
}

# Check both containers are running
echo "📋 Checking containers..."
check_container $WEB_CONTAINER
check_container $DB_CONTAINER

# Save Web Container
echo "💾 Saving web container ($WEB_CONTAINER)..."
docker commit $WEB_CONTAINER $WEB_IMAGE:latest
docker tag $WEB_IMAGE:latest $WEB_IMAGE:$TIMESTAMP

echo "☁️  Pushing web container to GitHub Container Registry..."
docker push $WEB_IMAGE:latest
docker push $WEB_IMAGE:$TIMESTAMP

# Save Database Container
echo "💾 Saving database container ($DB_CONTAINER)..."
docker commit $DB_CONTAINER $DB_IMAGE:latest
docker tag $DB_IMAGE:latest $DB_IMAGE:$TIMESTAMP

echo "☁️  Pushing database container to GitHub Container Registry..."
docker push $DB_IMAGE:latest
docker push $DB_IMAGE:$TIMESTAMP

echo "✅ Both containers saved successfully!"
echo "🏷️  Web Container:"
echo "    Latest: $WEB_IMAGE:latest"
echo "    Backup: $WEB_IMAGE:$TIMESTAMP"
echo "🏷️  Database Container:"
echo "    Latest: $DB_IMAGE:latest"
echo "    Backup: $DB_IMAGE:$TIMESTAMP"
echo ""
echo "📝 To resume on another machine, run the resume script with these images."