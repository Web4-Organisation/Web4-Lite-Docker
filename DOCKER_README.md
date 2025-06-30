# Docker Setup for Web4 Lite

This document describes how to set up and run this project using Docker and Docker Compose.

## What are Docker and Docker Containers?

**Docker** is an open-source platform that allows developers to build, deploy, and run applications in so-called **containers**.

A **container** is a lightweight, standalone, executable unit that contains everything needed to run an application: code, runtime, system tools, system libraries, and settings. Containers isolate applications from each other and from the underlying infrastructure, leading to consistency across different environments (development, testing, production).

**Docker Compose** is a tool for defining and running multi-container Docker applications. With a YAML file (`docker-compose.yml`), you can configure the services, networks, and volumes of your application, and then start all services with a single command.

## Benefits of Using Docker for This Project

- **Consistent Environment**: Ensures that the application runs in the same environment across development, testing, and production, reducing the "It works on my machine" problem.
- **Easy Setup**: New developers can get the project up and running quickly without manually installing and configuring dependencies like PHP, Apache, and MySQL.
- **Isolation**: The project environment is isolated from the local machine, preventing conflicts with other projects or globally installed software.
- **Portability**: The containerized application can easily run on any system with Docker installed.
- **Scalability**: Docker containers can be scaled easily when needed (although this specific setup may require additional configuration for that).

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (for Windows, macOS, or Linux) must be installed. This includes Docker Engine and Docker CLI.

## Setting Up and Starting the Application

1.  **Clone the repository** (if not already done):
    ```bash
    git clone <repository-url>
    cd <repository-directory>
    ```

2.  **Adjust database configuration**:
    Open the file `sys/config/db.inc.php`. You need to modify the following lines to match the database details defined in `docker-compose.yml`:

    ```php
    // ... other configurations ...

    //Please edit database data

    $C['DB_HOST'] = "db";                                         // Docker service name for MySQL
    $C['DB_USER'] = "app_user";                                   // Matches MYSQL_USER in docker-compose.yml
    $C['DB_PASS'] = "app_password";                               // Matches MYSQL_PASSWORD in docker-compose.yml
    $C['DB_NAME'] = "app_db";                                     // Matches MYSQL_DATABASE in docker-compose.yml

    // ... remaining configurations ...
    ```
    **Note**: The values for `DB_USER`, `DB_PASS`, and `DB_NAME` must match the values for `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_DATABASE` set in the `db` service in the `docker-compose.yml` file. `DB_HOST` should be set to `db`, as this is the name of the MySQL service within the Docker network.

3.  **Start the application with Docker Compose**:
    In the root directory of the project (where `docker-compose.yml` is located), run the following command:
    ```bash
    docker-compose up -d
    ```
    -   `up`: Builds and starts the containers.
    -   `-d`: Runs the containers in the background (detached mode).

    The first time you run this command, Docker will download the required images (PHP, MySQL) and build the application image. This may take a few minutes. Subsequent startups are faster.

4.  **Access the application**:
    Once the containers are running, the web application is accessible at [http://localhost:8080](http://localhost:8080) in your web browser.

5.  **Database import (if needed)**:
    If you have an existing database dump file (`.sql`) that you want to import:
    a.  Find the container ID of the MySQL container: `docker ps`
    b.  Copy the SQL file into the MySQL container: `docker cp your_dump_file.sql <mysql_container_id>:/tmp/your_dump_file.sql`
    c.  Access the shell of the MySQL container: `docker exec -it <mysql_container_id> bash`
    d.  Import the database: `mysql -u root -p<MYSQL_ROOT_PASSWORD> <MYSQL_DATABASE> < /tmp/your_dump_file.sql` (Replace `<MYSQL_ROOT_PASSWORD>` and `<MYSQL_DATABASE>` with the values from `docker-compose.yml`). Note that there is no space between `-p` and the password.
    e.  Exit the container shell: `exit`

    For a fresh installation, you may need to run the application’s installation process (if available), or manually create/import the required tables. The application expects a usable database schema.

## Useful Docker Commands

-   **Stop containers**:
    ```bash
    docker-compose down
    ```
    This stops and removes the containers, but the database volume (`db_data`) is retained so your data will still be available on the next start.

-   **Stop containers and remove volumes** (Warning: This deletes the database data):
    ```bash
    docker-compose down -v
    ```

-   **Show status of running containers**:
    ```bash
    docker-compose ps
    ```
    Or for all Docker containers:
    ```bash
    docker ps -a
    ```

-   **Show container logs**:
    ```bash
    docker-compose logs -f <service_name>
    ```
    (e.g. `docker-compose logs -f web` or `docker-compose logs -f db`)
    The `-f` stands for "follow" and streams the logs live.

-   **Run a command in a running container**:
    ```bash
    docker-compose exec <service_name> <command>
    ```
    (e.g. to open a bash shell in the web container: `docker-compose exec web bash`)

-   **Rebuild the application image** (if you made changes to the Dockerfile):
    ```bash
    docker-compose build
    ```
    Or to rebuild a specific service image:
    ```bash
    docker-compose build web
    ```
    Often it's best to use `docker-compose up -d --build` to rebuild and restart in one step.

## Troubleshooting

-   **Port conflicts**: If port `8080` or `3306` is already in use on your host system, change the port mappings in `docker-compose.yml` (e.g. `"8081:80"` for the web app).
-   **Permission issues**: If the application has trouble writing files (e.g. uploads, cache), check file permissions inside the container. The line `RUN chown -R www-data:www-data /var/www/html` in the Dockerfile should fix most of these issues.
-   **Database connection problems**:
    -   Make sure the `db` container is running (`docker-compose ps`).
    -   Check the logs of the `db` container (`docker-compose logs db`) for errors.
    -   Ensure that the credentials in `sys/config/db.inc.php` exactly match those in `docker-compose.yml` for the `db` service, and that `DB_HOST` is set to `db`.
-   **Composer issues**: If there are problems during the `composer install` step, check the build logs: `docker-compose build --no-cache web`. Network issues or missing PHP extensions are common causes. The current Dockerfile tries to install the most common extensions.

For additional problems, the logs of the respective containers (`docker-compose logs web` or `docker-compose logs db`) often provide insight into the cause.
