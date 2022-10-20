# Using Docker Compose with WordPress

Once you have Docker and Docker Compose installed, just run `docker-compose up` and WordPress will become available on http://localhost:80.

Docker containers sometimes throw erros because of issues with persistent state. If you see any Docker-related issues, clear everything out by running `docker-compose down --volumes --remove-orphans`.