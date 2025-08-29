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
source <(curl --fail-with-body -sSL http://rah.localhost/install.sh)
````

### basic usage
Basic upload (with override):
````bash
# the install.sh automatically exports the RAH_API for you
source <(curl --fail-with-body -sSL http://rah.localhost/install.sh)
# RAH_API_KEY is generated and placed in storage/rah-api-key.txt + it is printed in the docker log on startup
export RAH_API_KEY=rah_...
rah upload dist/public/ .
````

#### Autocompletion

````bash
# activate autocompletion for rah cli tool (temporarily)
eval "$(rah completion $(basename $SHELL))"
# activate autocompletion for rah cli tool (permanently) (bash)
echo 'eval "$(rah completion bash)"' >> ~/.bashrc
````

### gitlab CI/CD example:
````yaml
build:
  stage: build
  image: node:22
  script:
    - yarn
    - yarn lint
    - yarn test:unit
    - yarn build
    - source <(curl --fail-with-body -sSL https://rah.andersundsehr.com/install.sh)
    - rah upload .histoire/dist .
  environment:
    name: review/$CI_COMMIT_REF_SLUG
    url: https://rah.andersundsehr.com/redirect?project=$CI_PROJECT_PATH_SLUG&deployment=$CI_COMMIT_REF_SLUG&path=.
````

### bitbucket Pipelines example:
````yaml
pipelines:
  default:
    - step:
        name: Build and Deploy
        image: node:22
        script:
          - yarn
          - yarn lint
          - yarn test:unit
          - yarn build
          - source <(curl --fail-with-body -sSL https://rah.andersundsehr.com/install.sh)
          - rah upload .histoire/dist .
````

### github Actions example:
````yaml
name: Build and Deploy

on:
  push:
    branches:
      - main
      - master
      - '**'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Use Node.js 22
        uses: actions/setup-node@v4
        with:
          node-version: 22
      - run: yarn
      - run: yarn lint
      - run: yarn test:unit
      - run: yarn build
      - name: Install rah
        run: source <(curl --fail-with-body -sSL https://rah.andersundsehr.com/install.sh)
      - name: Upload
        run: rah upload .histoire/dist .
````


### _headers file: Custom HTTP headers for your deployment

You can control HTTP headers for your deployed static site by adding a `_headers` file to the root of your deployment. This allows you to set security headers, cache control, and more, on a per-path basis.

#### Syntax
- Each rule starts with a path pattern (e.g. `/*`, `/api/*`, `/index.html`).
- Under each rule, add one or more indented header lines in the format `Header-Name: value`.
- Header lines **must be indented** (with spaces or tabs).
- Header names may only contain letters, digits, and hyphens.
- Lines starting with `#` are comments and are ignored.
- Empty lines are ignored.

#### Example
```
/*
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
# This is a comment
/other
  X-Frame-Options: SAMEORIGIN
```

#### Validation and error handling
- If a rule is defined without any headers, an error is reported with the line number.
- If a header line is not indented, an error is reported with the line number.
- If a header name contains invalid characters, an error is reported with the line number.
- If a path rule contains a colon (`:`), an error is reported (placeholders are not supported).

#### Skipped headers
For security and compatibility, the following headers are ignored and will not be set:
- Accept-Ranges
- Age
- Allow
- Alt-Svc
- Connection
- Content-Encoding
- Content-Length
- Content-Range
- Date
- Server
- Trailer
- Transfer-Encoding
- Upgrade

This feature allows you to easily add security and caching headers to your static deployments, similar to Netlify's `_headers` file format.

### environment variables client

ENV variables:
- `RAH_API` - url of the server (required, automatically exported by install.sh)
- `RAH_PROJECTNAME` - name of the project (autodetected, required)
- `RAH_DEPLOYMENT` - name of the deployment (autodetected, required)
- `RAH_DEPLOYMENT_MESSAGE` - description of the deployment (autodetected, required)
- `RAH_DEFAULT_DEPLOYMENT` - set the default deployment (autodetected, required)

all of these are automatically detected for almost all CI systems. (see https://github.com/OndraM/ci-detector)

#### customized example

````bash
source <(curl --fail-with-body -sSL http://rah.localhost/install.sh)
export RAH_PROJECTNAME="my-project"
export RAH_DEPLOYMENT="my-deployment"
export RAH_DEFAULT_DEPLOYMENT="my-deployment"

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
- `https://<PROJECT>--<DEPLOYMENT>.<HOSTNAME>/` or if too long it will be hashed: eg `https://project-with-many-tools-that-really-is-too-long--1a380--histoire.<HOSTNAME>/`
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
  "api": "https://rah.andersundsehr.com",
  "projectName": "andersundsehr-project",
  "deployment": "feature-helps-all",
  "deploymentMessage": "✨ feat: add new component \"helps\"",
  "defaultDeployment": "main"
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

###### Future ideas:

- maybe:
  - defaultDeployment implement 
    - default branch is always on top of the list
    - default branch will be deleted last
  - add url to repo
  - add url to branch
  - add url to pipeline
  - lookup if branch is deleted and than delete the deployment?
