# RAH (review-and-host)

is an docker image with that you can host your SPA app and review your PRs with a preview link.

## Functions:

- host infinitely many projects and reports (as long as you have disk space)
- host infinitely many parallel deployments (as long as you have disk space)
- auto cleanup of old deployments
- a deployment has a defined lifetime
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

### delete command

````bash
rah delete-deployment
# or
rah delete-project
````

### get url command

````bash
rah url
````

## docker container:

`compose.yml`
````bash
services:
  rah:
    image: andersundsehr/rah
    volumes:
      - ./rah:/storage
    environment:
      # RAH_API_KEY is generated and placed in rah/.env + it is printed in the docker log on startup
      RAH_HOSTNAME: ${RAH_HOSTNAME:-rah.localhost}
      RAH_PORT: ${RAH_PORT:-80}
      RAH_STORAGE_PATH: '/storage'
      # allows access to all deployments if client has ip from list
      RAH_AUTH_IPS: "213.61.68.122,127.0.0.1"
      # allows access to all deployments if client uses this auth
      RAH_BASIC_AUTH: "rah:rah"
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

- rah binary with build in php
- docker build
- add cronjob for background deletions of old deployments

- defaultDeployment remove/or implement
- deleteIfMissingMr remove/or implement
- deleteIfMissingBranch remove/or implement
