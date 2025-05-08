# RAH (review-and-host)

is an docker image with that you can host your SPA app and review your PRs with a preview link.

## Functions:

- host infinitely many projects and reports (until RAH_MAX_DISK_USAGE is hit)
- host infinitely many parallel deployments (until RAH_MAX_DISK_USAGE is hit)
- auto cleanup of old deployments (default branches will be cleanup up last)
- a deployment can be uploaded with replace or append
- a deployment can be uploaded with a custom name

## client usage (CLI)

### install

````bash
source <(curl -sSL http://rah.localhost/install.sh)
````

### basic usage
Basic upload (with override):
````bash
# the install.sh automatically exports the RAH_API for you
source <(curl -sSL http://rah.localhost/install.sh)
# RAH_API_KEY is generated and placed in storage/rah-api-key.txt + it is printed in the docker log on startup
export RAH_API_KEY=rah_...
rah upload dist/public/ .
````

### environment variables client

ENV variables:
- `RAH_API` - url of the server (required, automatically exported by install.sh)
- `RAH_PROJECTNAME` - name of the project (autodetected, required)
- `RAH_DEPLOYMENT` - name of the deployment (autodetected, required)
- `RAH_DEPLOYMENT_MESSAGE` - description of the deployment (autodetected, required)
- `RAH_DEFAULT_DEPLOYMENT` - set the default deployment (autodetected, required)
- `RAH_DELETE_AFTER` - when the deployment should be deleted (optional) (ISO 8601 format or 1d, 1w, 1m, 1y) (default 1m)
- `RAH_DELETE_IF_MISSING_BRANCH` - should be deleted if the branch is deleted (optional) `dose nothing right now`

autodetection gitlab:
- `RAH_PROJECTNAME=$CI_PROJECT_PATH_SLUG`
- `RAH_DEPLOYMENT=$CI_COMMIT_REF_SLUG`
- `RAH_DEPLOYMENT_MESSAGE=$CI_COMMIT_MESSAGE`
- `RAH_DEFAULT_DEPLOYMENT=$CI_DEFAULT_BRANCH`
- `RAH_DELETE_IF_MISSING_BRANCH=$CI_COMMIT_BRANCH`

#### customized example

````bash
curl -sSL https://rah.example.com/install.sh | bash
export RAH_PROJECTNAME="my-project"
export RAH_DEPLOYMENT="my-deployment"
export RAH_DEFAULT_DEPLOYMENT="my-deployment"
export RAH_DELETE_AFTER="2y"
export RAH_DELETE_IF_MISSING_BRANCH="main"

### add additional files (eg. reports)
Add additional files (reports) to the deployment: 
````bash
rah append .playwright/report/ reports/
````

## docker container:

`compose.yml`
````bash
services:
  rah:
    image: andersundsehr/rah:1
    init: true
    volumes:
      - storage:/storage
    environment:
      # RAH_API_KEY is generated and placed in storage/rah-api-key.txt + it is printed in the docker log on startup
      RAH_HOSTNAME: ${RAH_HOSTNAME:-rah.localhost}
      RAH_STORAGE_PATH: '/storage'
      # max disk usage for the whole rah storage (will auto delete old deployments) (K M G T P E supported)
      RAH_MAX_DISK_USAGE: '10G'
      # allows access if client has one of the IPs can accept ranges eg: 127.0.0.0/8 or special private_ranges (shortcut for private IP address ranges of your proxy)
      RAH_AUTH_IPS: "213.61.68.122,213.61.68.0/24,private_ranges"
      # allows access if client uses the basic auth user and password (multiple users can be separated with a comma)
      RAH_BASIC_AUTH: "rah:passRah,customer:passCustomer"
    logging:
      driver: "json-file"
      options:
        max-size: "100m"
volumes:
  storage:
````

### URL structure

- `https://<HOSTNAME>/` shows the dashboard of all deployed projects
- `https://<PROJECT>.<HOSTNAME>/` shows the dashboard of the project
- `https://<PROJECT>--<DEPLOYMENT>.<HOSTNAME>/`
- `https://<HOSTNAME>/api/` api to upload deployments
- `https://<HOSTNAME>/install.sh` bash script to install `rah` cli tool

### dashboard overall:

includes:
- list of all projects, per project:
  - name
  - number of deployments
  - disk usage
  - link to default deployment (name and relative date of deployment)
  - link to latest deployment (name and relative date of deployment)
  - link to project dashboard

### dashboard project:
includes:
- list of all deployments, per deployment:
  - name
  - relative date of deployment
  - disk usage
  - link to deployment
  - delete button
  - date of auto deletion (button to increase the date?)


### INTERNAL: folder structure and deployment metadata

`<RAH_STORAGE_PATH>/<PROJECT>/<DEPLOYMENT_NAME>`
- `RAH_STORAGE_PATH` (env) is defined as environment variable inside the container
- `PROJECT` is the name of the project
- `DEPLOYMENT_NAME` is the name of the deployment (even Prod needs a name) can be [a-z0-9]

project metadata is stored inside `<RAH_STORAGE_PATH>/<PROJECT>/project.json` file.

````json5
{
  "defaultDeployment": "main" // redirect to a specific deployment (optional)
}
````

metadata is stored inside `<RAH_STORAGE_PATH>/<PROJECT>/<DEPLOYMENT_NAME>/deployment.json` file.
````json5
{
  "deleteAfter": "2025-01-01T00:00:00Z", // when the deployment should be deleted (optional)
  "deleteIfMissingMr": 123, // should be deleted if the mr is closed (optional)
  "deleteIfMissingBranch": "main", // should be deleted if the branch is deleted (optional)
}
````

## Running Tests

This project uses PHPUnit for testing. To run the tests, follow these steps:

1. Ensure you have installed the development dependencies:
   ````bash
   docker compose exec --user 1000:1000 rah composer install
   ````

2. Run the tests using the following command:
   ````bash
   docker compose exec --user 1000:1000 rah composer test
   ````

## Code Coverage

This project includes automated tests with code coverage reporting. 

To run the tests and generate an updated code coverage report, use the following command:

```bash
docker compose exec --user 1000:1000 rah composer test
```

The HTML report will be available in the `build/coverage` directory. Open `build/coverage/index.html` in a web browser to view detailed coverage information.


###### TODO

- needed:
  - CI build
- maybe:
  - defaultDeployment remove/or implement
  - deleteIfMissingMr remove/or implement
  - deleteIfMissingBranch remove/or implement
  - add url to repo
  - add url to branch
