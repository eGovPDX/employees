[![CircleCI](https://circleci.com/gh/eGovPDX/employees.svg?style=svg)](https://circleci.com/gh/eGovPDX/employees)

# Portland Employees intranet site

## Get the code

`git clone git@github.com:eGovPDX/employees.git` will create a folder called `employees` whereever you run it and pull down the code from the repo.

## Git setup for our Windows developers

Windows handles line endings differently than \*nix based systems (Unix, Linux, macOS). To make sure our code is interoperable with the Linux servers to which they are deployed and to the local Linux containers where you develop, you will need to make sure your git configuration is set to properly handle line endings.

We want the repo to correctly pull down symlinks for use in the Lando containers.git. To do this, we will enable symlinks as part of the cloning of the repo.

`git clone -c core.symlinks=true git@github.com:eGovPDX/employees.git`

`git clone` and `git checkout` must be run as an administrator in order to create symbolic links
(run either Command Prompt or Windows Powershell as administrator for this step)

Run `git config core.autocrlf false` to make sure this repository does not try to convert line endings for your Windows machine.

## Installing Lando

Follow the steps at https://docs.devwithlando.io/installation/installing.html

## Uninstalling Lando

Follow the steps at https://docs.devwithlando.io/installation/uninstalling.html
To completely remove all traces of Lando, follow the "removing lingering Lando configuration" steps

## Using Lando

The .lando.yml file included in this repo will set you up to connect to the correct Pantheon dev environment for the Portland Oregon website. To initialize your local site for the first time, follow the steps below:

1. From within your project root directory, run `lando start` to initialize your containers.
2. Log in to Pantheon and generate a machine token from My Dashboard > Account > Machine Tokens.
3. Run `lando terminus auth:login --machine-token=[YOUR MACHINE TOKEN]`, this logs your Lando instance into our Pantheon account.
4. To make sure you don't hit rate limits with composer, log into Github and generate a personal access token and add it to your lando instance by using `lando composer config --global --auth github-oauth.github.com "$COMPOSER_TOKEN"`. (You should replace \$COMPOSER_TOKEN with your generated token.) There is a handy tutorial for this at https://coderwall.com/p/kz4egw/composer-install-github-rate-limiting-work-around
5. **If this is a new clone of the repo:** before continuing to the next step you must run `lando composer install` and `lando npm install` to install the appropriate dependencies.
6. You have three options to get your database and files set up:
   1. Lando quick start:
      1. Run `lando pull` to get the DB and files from pantheon. This process takes a while. #grabsomecoffee
   2. Run `lando latest` to automaticaly download and import the latest DB from Dev.
   3. Manually import the database and files
      1. Download the latest database and files backup from the Dev environment on Pantheon. (https://dashboard.pantheon.io/sites/5c6715db-abac-4633-ada8-1c9efe354629#dev/backups/backup-log)
      2. Move your database export into a folder in your project root called `/artifacts`. (We added this to our .gitignore, so the directory won't be there until you create it.)
      3. Run `lando db-import artifacts/employees_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.)
      4. Move your files backup into `web/sites/default/files`
7. Run `git checkout master` and `lando refresh` to build your local environment to match the master branch. (This runs composer install, drush updb, drush cim, and drush cr.)
8. You should now be able to visit https://employees.lndo.site in your browser.
9. When you are done with your development for the day, run `lando stop` to shut off your development containers.
10. To set up XDebug, see https://docs.devwithlando.io/tutorials/php.html#toggling-xdebug and https://docs.devwithlando.io/guides/lando-with-vscode.html.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Theme development

If you have modified the Westy theme, you can run `lando npm run build` to locally rebuild the CSS/JS assets. Alternatively if you are actively working on the theme, you can run `lando npm run watch` to watch files in the background and automatically build them.

## Local development mode

By default the site runs in "development" mode locally, which means that caching is off and twig debugging is on, etc. These settings are managed in web/sites/default/local.services.yml. While it is possible to update these settings if the developer wishes to run the site with caching on and twig debug off, updates to this file should never be comitted in the repo, so that developers are always working in dev mode by default.

## Workflow for this repository

We are using a modified version of GitHub Flow to keep things simple. While you don't need to fork the repo if you are on the eGov dev team, you will need to issue pull requests from a feature branch in order to get your code into our `master` branch. The `master` branch builds to the Pantheon `dev` environment.

To best work with Pantheon Multidev, we are going to keep feature branch names simple and use the master branch as our integration point that builds to the Pantheon Dev environment.

### Start new work in your local environment

```bash
git checkout master
git pull origin master
lando latest
lando refresh
git checkout -b pe-[ID]
```

<details>

1. Verify you are on the master branch with `git checkout master`.
2. On the master branch, run `git pull origin master`. This will make sure you have the latest changes from the remote master. Optionally, running `git pull -p origin` will prune any local branches not on the remote to help keep your local repo clean.
3. Use the issue ID from Jira for a new feature branch name to start work: `git checkout -b pe-[ID]` to create and checkout a new branch. (We use lowercase for the branch name to help create Pantheon multidev environments correctly.) If the branch already exists, you may use `git checkout pe-[ID]` to switch to your branch. If you need to create multiple multidevs for your story, name your additional branches `pe-[ID][a-z]` or `pe-[ID]-[a-z or 1-9]` (but continue use just `PE-[ID]` in the git commits and PR titles for all branches relating to that Jira story).

   **TLDR:**
    - New feature branch
        ```
        git checkout -b pe-[ID]
        ```
    - New branch from current branch
        ```
        pe-[ID][a-z]` or `pe-[ID]-[a-z or 1-9]
        ```
    - Use base branch ID for base/sub-branch commits and PR titles
        ```
        PE-[ID]
        // on base branch pe-123
        git commit -m "PE-123 ..."
        // on pe-123-a branched from pe-123
        git commit -m "PE-123 ..."
        ```

4. Run `lando latest` at the start of every sprint, or as directed by the build lead, to update your local database with a copy of the database from Test.
5. Run `lando refresh` to refresh your local environment's dependencies and config. (This runs composer install, drush updb, drush cim, and drush cr.)
6. You are now ready to develop on your feature branch.

</details>

### Workflows for Composer patches

[Official documentation on recommended workflows](https://docs.cweagans.net/composer-patches/usage/recommended-workflows/)

#### Adding a patch

1. Add the patch definition to `patches.json`.
2. Run `lando composer patches-relock` to regenerate `patches.lock.json` with your new patch.
3. Run `lando composer patches-repatch` to reapply the defined patches to all dependencies.

#### Removing a patch

1. Delete the patch definition from `patches.json`.
2. Run `lando composer patches-relock` to regenerate `patches.lock.json`.
3. Manually delete the dependency that you removed a patch from (the location of the dependency will vary, but a good starting point is to look in the /vendor or /web/modules/contrib directories).
4. Run `lando composer patches-repatch` to reapply the defined patches to all dependencies.

### Commit code and push to Github

1. In addition to any custom modules or theming files you may have created, you need to export any configuraiton changes to the repo in order for those changes to be synchronized. Run `lando drush cex` (config-export) in your local envionrment to create/update/delete the necessary config files. You will need to commit these to git.
2. To commit your work, run `git add -A` to add all of the changes to your local repo. (If you prefer to be a little more precise, you can `git add [filename]` for each file or several files separated by a space.
3. Then create a commit with a comment, such as `git commit -m "PE-[ID] description of your change."`
4. Just before you push to GitHub, you should rebase your feature branch on the tip of the latest remote master branch. To do this run `git fetch origin master` then `git rebase -i origin/master`. This lets you "interactively" replay your change on the tip of the current release branch. You'll need to pick, squash or drop your changes and resolve any conflicts to get a clean commit that can be pushed to release. You may need to `git rebase --continue` until all of your changes have been replayed and your branch is clean. 
   - You may also choose to mrege in `master` (`git merge origin master`) if your branch has many commits that would make a rebase difficult or if you have already pushed your branch to Github.
5. Run `lando refresh` to refresh your local environment with any changes from master. (This runs composer install, drush updb, drush cim, and drush cr.)
6. You can now run `git push -u origin pe-[ID]`. This will push your feature branch and set its remote to a branch of the same name on origin (GitHub).

### Create a pull request

When your work is ready for code review and merge:

- Create a Pull Request (PR) on GitHub for your feature branch, it will default to branching from `master`.
- Make sure to include PE-[ID] and a short description of the feature in your PR title so that Jira can relate that PR to the correct issue. This also helps with writing release notes.

### Continuous integration on CircleCI

1. The PR creation triggers an automated CircleCI build, deployment, and test process that will:
   - Check out code from your working branch on GitHub.
   - Run `composer install`.
   - Deploy the site to a multidev feature branch site on Pantheon.
   - Run `drush cim` to import config changes.
   - Run `drush updb` to update the database.
   - Run `drush cr` to rebuild the caches.
   - Run smoke tests against the feature branch site to make sure the build was successful.
2. If the build fails, you can go back to your local project and correct any issues and repeat the process of getting a clean commit pushed to GitHub. Once a PR exists, every commit to that branch will trigger a new CircleCI build. You only need to run `git push` from your branch if it is already connected to the remote, but you'll probably want to step through the rebase steps if the time between pushes is anything less than a couple of minutes.

### Pantheon MultiDev site

1. You'll need to prep anything on the multidev site that is needed for QA to complete the test plan. This is also a chance to see if you need to address any issues with an additional commit.
2. In Jira, update the test plan of your issue including the URL to the feature branch. Move the issue to "QA" and assign the issue to the QA team.

### QA

1. Executes the test plan step by step. (As Rick likes to say, "try and break it! Be creative!")
2. If defects are found, communicate with the developer and move the issue back to "Todo" in Jira and assign it back to the developer. Document what you find as comments on the issue and decide with the developer if you need to open bugs to address in the future or if you should address the issue now.
3. If no defect is found, move the issue to "Merge and Accept" in Jira and assign it to the build master.

### Move to Merge and Accept

Go back to your PR in GitHub and make sure to assign at least one team member as a reviewer. Reviews are required before the code can be merged. We are mostly checking to make sure the merge is going to be clean, but if we are adding custom code, it is nice to have a second set of eyes checking for our coding standards.

## Build master

There are a few extra steps for the assigned build master. This person is the final sanity check before merging in changes to the Dev, Test and Live instances on Pantheon. Basically the Dev and Test deploys are an opportunity to practice and test the deployment of the feature.

### Bundling a release and deploying to Pantheon Dev site

1. After a team member has provided an approval, which may be after responding to feedback and resolving review issues, the build master will be able to push the "Squash and merge" button and commit the work to the `master` branch. 
    - Make sure the PR has `master` set as the base branch and that the merge message is prepended with the Jira issue ID (e.g. "PE-42 Adding the super duper feature")
   - The merge triggers an automated CircleCI build on the Dev environment.
2. Test that everything still works on the Dev site. This is just a sanity check since a QA has already been performed.
   - Can you confirm the expected code changes were deployed?
   - Do permissions need to be rebuilt?
   - If all work from the issue is merged and the build was successful, you can move the issue to the done column on our Jira board and delete the feature branch from Github.
3. Repeat steps 1-2 to merge additional PRs until you've bundled all of the changes together that you want to go into the next "deployment" to Test, and Live.
4. Before the last merge to `master` for the desired deployment. Clone the `live` database to `dev` using the following command: `lando terminus env:clone-content employees.live dev`
5. After the clone is complete, merging to master will trigger an automated CircleCI build, deployment, and test process on the Dev environment similar to the multidev deployment.
    - Verify that the CircleCI build on Dev is successful and passes our automated tests.

### Releases to Test (or Production)

We are using the Dev environment to bundle all the approved code together into a single release which can then be deployed to Test, and Live and make sure things are solid. At least once per week, or more frequently as needed, our combined changes should be pushed to the Test and Live environments. The test deployment is essentially the last check to see if our code will be safe on Production and build correctly as the Pantheon Quicksilver scripts operate in a slightly different environment than CircleCI's Terminus commands.

1. [Create a new release](https://github.com/eGovPDX/employees/releases/new) in Github.
    - Common convention is to use `v[SPRINT_NUMBER].[RELEASE_COUNT]` for the tag and release title. For example: v163.3 means the third release in Sprint 163.
    - Click the `Generate release notes` button to automatically generate release notes. You can make additional edits to the release notes as well.
    - Publish the new release.
1. (Optional) In the [Actions page](https://github.com/eGovPDX/employees/actions), watch the deployment job progress.
1. When the first job `Deploy to test` is finished, manually smoke test the Test site by visiting a few pages like site search, directory, news, group homepage, etc.
    - Visit the configuration sync and status report pages in the admin menu. If config is not imported, it may be necessary to synchronize the configuration by running `lando terminus drush employees.test cim -y`. Never use the Drupal configuration synchronization admin UI to run the config import. (There be dragons... and the Drush timeout is significantly longer than the UI timeout. The UI timeout can lead to config coruption and data loss.)
    - All reviewers will receive an email about the pending job that needs approval.
1. If everything works on the Test site, approve the Live deployment job in the [Actions page](https://github.com/eGovPDX/employees/actions).
1. After the approval, the `Deployment to live` starts. Repeat the step above to smoke test the Live site and review the configuration sync and status report pages.
1. Add a News item in the [City Web Editors group](https://employees.portland.gov/web-support) on the Employees portal.
    - There is a release notes template that can be cloned for a quick head start.

## Using Composer
Composer is built into our Lando implementation for package management. We use it primarily to manage Drupal contrib modules and libraries.
Here is a good guide to using Composer with Drupal: https://www.lullabot.com/articles/drupal-8-composer-best-practices
Composer cheat sheet: https://devhints.io/composer
### Installing contrib modules
Use `lando composer require drupal/[module name]` to download contrib modules and add them to the composer.json file. This ensures they're installed in each environment where the site is built. Drupal modules that are added this way must also be enabled using the `lando drush pm:enable [module name]` command.
### Updating contrib modules and lock file
In general it's a good practice to keep dependencies updated to latest versions, but that introduces the risk of new bugs from upstream dependencies. Updating all dependencies should be done judiciously, and usually only at the beginning of a sprint, when there's adequate time to regression test and resolve issues. Individual packages can be updated as needed, without as much risk to the project.
To update all dependencies, run `lando composer update`. To update a specific package, for example the Devel module, run `lando composer update --with-dependencies drupal/devel`. After updating, make sure to commit the updated composer.lock file.
The composer.lock file contains a commit hash that identifies the exact commit version of the lock file and all dependencies' dependencies. You can think of it as a tag for the exact combination of dependencies being committed, and it's used to determine whether composer.lock is out of date.
When something changes in composer.json, but the lock hash has not been updated, you may receive the warning:
...
The lock file is not up to date with the latest changes in composer.json. You may be getting outdated dependencies. Run update to update them.
...
To resolve this, run `lando composer update --lock`, which will generate a new hash. If you encounter a conflict on the hash value when you merge or rebase, use the most recent (yours) version of the hash.
