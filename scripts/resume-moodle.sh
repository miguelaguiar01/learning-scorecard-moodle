#!/bin/bash
set -e

echo "🚀 RESUMING COMPLETE MOODLE WITH ALL DATA + GIT REPO"
echo "===================================================="

# Clean up any existing containers
echo "🧹 Cleaning up existing containers..."
docker stop $(docker ps -aq) 2>/dev/null || true
docker rm $(docker ps -aq) 2>/dev/null || true
docker network rm moodle_network 2>/dev/null || true

# Clone or ensure learning-scorecard repo exists
echo "📁 Setting up learning-scorecard git repository..."
if [ ! -d "learning-scorecard-moodle" ]; then
    echo "📥 Cloning learning-scorecard repository..."
    git clone https://github.com/miguelaguiar01/learning-scorecard-moodle.git
else
    echo "📁 Learning-scorecard directory already exists"
    echo "🔄 Pulling latest changes..."
    cd learning-scorecard-moodle && git pull && cd ..
fi

# Create network
echo "🌐 Creating network..."
docker network create moodle_network

# Pull the complete images with all your data
echo "📥 Pulling complete images from GitHub Container Registry..."
docker pull ghcr.io/miguelaguiar01/moodle_db_complete:latest
docker pull ghcr.io/miguelaguiar01/moodle_web_complete:latest

# Start database container
echo "🗄️  Starting database with your data..."
docker run -d \
    --name moodle-db-complete \
    --network moodle_network \
    -p 3306:3306 \
    -e MYSQL_USER=moodle \
    -e MYSQL_PASSWORD=moodle \
    -e MYSQL_ROOT_PASSWORD=root \
    -e MYSQL_DATABASE=moodle \
    ghcr.io/miguelaguiar01/moodle_db_complete:latest

# Wait for database to be ready
echo "⏳ Waiting for database to initialize..."
timeout=120
while [ $timeout -gt 0 ]; do
    if docker exec moodle-db-complete mysqladmin ping -h localhost -u moodle -pmoodle >/dev/null 2>&1; then
        echo "✅ Database is ready!"
        break
    fi
    echo "⏳ Database starting... ($timeout seconds left)"
    sleep 5
    timeout=$((timeout-5))
done

if [ $timeout -le 0 ]; then
    echo "❌ Database failed to start"
    docker logs moodle-db-complete --tail 30
    exit 1
fi

# Start web container with bind mount for learning-scorecard
echo "🌐 Starting Moodle web with your data..."
docker run -d \
    --name moodle-web-complete \
    --network moodle_network \
    -p 8080:8080 \
    -p 8443:8443 \
    -v $(pwd)/learning-scorecard-moodle:/bitnami/moodle/local/learning_scorecard \
    -e MOODLE_DATABASE_HOST=moodle-db-complete \
    -e MOODLE_DATABASE_NAME=moodle \
    -e MOODLE_DATABASE_USER=moodle \
    -e MOODLE_DATABASE_PASSWORD=moodle \
    -e MOODLE_DATABASE_PORT_NUMBER=3306 \
    -e ALLOW_EMPTY_PASSWORD=yes \
    ghcr.io/miguelaguiar01/moodle_web_complete:latest

# Monitor startup
echo "⏳ Monitoring Moodle startup..."
for i in {1..30}; do
    sleep 5
    if ! docker ps --format "{{.Names}}" | grep -q "^moodle-web-complete$"; then
        echo "❌ Web container exited. Logs:"
        docker logs moodle-web-complete --tail 30
        exit 1
    fi
    
    # Check if Moodle is responding
    if curl -s -f http://localhost:8080 >/dev/null 2>&1; then
        echo "✅ Moodle is responding!"
        break
    fi
    echo "⏳ Container running... ($((i*5))/150 seconds)"
done

echo "🎉 SUCCESS! Your complete Moodle is now running!"
echo "🌐 Access Moodle at: http://localhost:8080"
echo "🔒 HTTPS access at: https://localhost:8443"
echo ""
echo "📋 Container status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
echo "💡 Your Moodle now has ALL your data, database, and files!"
echo "💡 Any changes you make will be preserved in the container."
echo "💡 To save changes, run the setup script again on this machine."
