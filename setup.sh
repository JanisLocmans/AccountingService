#!/bin/bash
set -e

# Colors for terminal output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting setup process...${NC}"

# Check if docker and docker-compose are installed
if ! command -v docker &> /dev/null; then
    echo "Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Step 1: Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file from template...${NC}"
    cp .env.dist .env
    echo -e "${GREEN}✓ Environment file created${NC}"
else
    echo -e "${GREEN}✓ Environment file already exists${NC}"
fi

# Step 2: Build and start Docker containers
echo -e "${YELLOW}Building and starting Docker containers...${NC}"
docker-compose up -d --build
echo -e "${GREEN}✓ Docker containers started${NC}"

# Step 3: Wait for the database to be ready
echo -e "${YELLOW}Waiting for the database to be ready...${NC}"
sleep 10
echo -e "${GREEN}✓ Database should be ready now${NC}"

# Step 4: Install Composer dependencies
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
docker-compose exec -T app composer install
echo -e "${GREEN}✓ Composer dependencies installed${NC}"

# Step 5: Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
echo -e "${GREEN}✓ Database migrations completed${NC}"

# Step 6: Load fixtures
echo -e "${YELLOW}Loading fixtures...${NC}"
docker-compose exec -T app php bin/console doctrine:fixtures:load --no-interaction
echo -e "${GREEN}✓ Fixtures loaded${NC}"

# Step 7: Create log directory and set permissions
echo -e "${YELLOW}Setting up log directory...${NC}"
docker-compose exec -T app mkdir -p var/log
docker-compose exec -T app chmod -R 777 var/log
docker-compose exec -T app touch var/log/exchange_rates.log
docker-compose exec -T app chmod 666 var/log/exchange_rates.log
echo -e "${GREEN}✓ Log directory set up${NC}"

# Step 8: Run code sniffer to check code quality
echo -e "${YELLOW}Running code sniffer...${NC}"
docker-compose exec -T app vendor/bin/phpcs
echo -e "${GREEN}✓ Code quality check completed${NC}"

# Step 9: Start the exchange rate scheduler worker in the background
echo -e "${YELLOW}Starting exchange rate scheduler worker...${NC}"
docker-compose exec -d app php bin/console app:simple-scheduler-worker
echo -e "${GREEN}✓ Exchange rate scheduler worker started${NC}"

echo -e "\n${GREEN}Setup completed successfully!${NC}"
echo -e "The application is now running at ${YELLOW}http://localhost${NC}"
echo -e "\nTo view exchange rate logs:"
echo -e "${YELLOW}docker-compose exec app cat var/log/exchange_rates.log${NC}"
echo -e "\nTo stop the application:"
echo -e "${YELLOW}docker-compose down${NC}"
