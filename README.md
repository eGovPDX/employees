# Intranet site for City of Portland employees

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
5. You have three options to get your database and files set up:
   1. Lando quick start:
      1. Run `lando pull` to get the DB and files from pantheon. This process takes a while. #grabsomecoffee
   2. Run `lando latest` to automaticaly download and import the latest DB from Dev.
   3. Manually import the database and files
      1. Download the latest database and files backup from the Dev environment on Pantheon. (https://dashboard.pantheon.io/sites/5c6715db-abac-4633-ada8-1c9efe354629#dev/backups/backup-log)
      2. Move your database export into a folder in your project root called `/artifacts`. (We added this to our .gitignore, so the directory won't be there until you create it.)
      3. Run `lando db-import artifacts/employees_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.)
      4. Move your files backup into `web/sites/default/files`
6. Run `git checkout release` and `lando refresh` to build your local environment to match the release branch. (This runs composer install, drush updb, drush cim, and drush cr.)
7. You should now be able to visit https://employees.lndo.site in your browser.
8. When you are done with your development for the day, run `lando stop` to shut off your development containers.
9. To set up XDebug, see https://docs.devwithlando.io/tutorials/php.html#toggling-xdebug and https://docs.devwithlando.io/guides/lando-with-vscode.html.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Local development mode

By default the site runs in "development" mode locally, which means that caching is off and twig debugging is on, etc. These settings are managed in web/sites/default/local.services.yml. While it is possible to update these settings if the developer wishes to run the site with caching on and twig debug off, updates to this file should never be comitted in the repo, so that developers are always working in dev mode by default.

## Workflow for this repository

We are using a modified version of GitHub Flow to keep things simple. While you don't need to fork the repo if you are on the eGov dev team, you will need to issue pull requests from a feature branch in order to get your code into our `release` branch. Release is a protected branch (similar to `master`) that we use to stage our commits until we're ready to issue a combined PR to merge everything from `release` into `master`.

To best work with Pantheon Multidev, we are going to keep feature branch names simple and use the master branch as our integration point that builds to the Pantheon Dev environment.

### Start new work in your local environment

```bash
git checkout release
git pull origin release
lando latest
lando refresh
git checkout -b powr-[ID]
```

<details>

1. Verify you are on the release branch with `git checkout release`.
2. On the release branch, run `git pull origin release`. This will make sure you have the latest changes from the remote release. Optionally, running `git pull -p origin` will prune any local branches not on the remote to help keep your local repo clean.
3. Use the issue ID from Jira for a new feature branch name to start work: `git checkout -b powr-[ID]` to create and checkout a new branch. (We use lowercase for the branch name to help create Pantheon multidev environments correctly.) If the branch already exists, you may use `git checkout powr-[ID]` to switch to your branch. If you need to create multiple multidevs for your story, name your additional branches `powr-[ID][a-z]` or `powr-[ID]-[a-z or 1-9]` (but continue use just `POWR-[ID]` in the git commits and PR titles for all branches relating to that Jira story).

   **TLDR:**
    - New feature branch
        ```
        git checkout -b powr-[ID]
        ```
    - New branch from current branch
        ```
        powr-[ID][a-z]` or `powr-[ID]-[a-z or 1-9]
        ```
    - Use base branch ID for base/sub-branch commits and PR titles
        ```
        POWR-[ID]
        // on base branch powr-123
        git commit -m "POWR-123 ..."
        // on powr-123-a branched from powr-123
        git commit -m "POWR-123 ..."
        ```

4. Run `lando latest` at the start of every sprint to update your local database with a copy of the database from Dev.
5. Run `lando refresh` to refresh your local environment's dependencies and config. (This runs composer install, drush updb, drush cim, and drush cr.)
6. You are now ready to develop on your feature branch.

</details>

### Commit code and push to Github

1. In addition to any custom modules or theming files you may have created, you need to export any configuraiton changes to the repo in order for those changes to be synchronized. Run `lando drush cex` (config-export) in your local envionrment to create/update/delete the necessary config files. You will need to commit these to git.
2. To commit your work, run `git add -A` to add all of the changes to your local repo. (If you prefer to be a little more precise, you can `git add [filename]` for each file or several files separated by a space.
3. Then create a commit with a comment, such as `git commit -m "POWR-[ID] description of your change."`
4. Just before you push to GitHub, you should rebase your feature branch on the tip of the latest remote release branch. To do this run `git fetch origin release` then `git rebase -i origin/release`. This lets you "interactively" replay your change on the tip of the current release branch. You'll need to pick, squash or drop your changes and resolve any conflicts to get a clean commit that can be pushed to release. You may need to `git rebase --continue` until all of your changes have been replayed and your branch is clean.
5. Run `lando refresh` to refresh your local environment with any changes from release. (This runs composer install, drush updb, drush cim, and drush cr.)
6. You can now run `git push -u origin powr-[ID]`. This will push your feature branch and set its remote to a branch of the same name on origin (GitHub).

### Create a pull request

When your work is ready for code review and merge:

- Create a Pull Request (PR) on GitHub for your feature branch, making sure to change the base branch from `master` to `release` because we want feature branches to be merged into `release` first.
- Make sure to include POWR-[ID] and a short description of the feature in your PR title so that Jira can relate that PR to the correct issue.

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

1. After a team member has provided an approval, which may be after responding to feedback and resolving review issues, the build master will be able to push the "Squash and merge" button and commit the work to the `release` branch. 
    - Make sure the PR has `release` set as the base branch and that the merge message is prepended with the Jira issue ID (e.g. "POWR-42 Adding the super duper feature")
   - The merge triggers an automated CircleCI build on the Release multidev site.
2. Test that everything still works on the Release multidev site. This is just a sanity check since a QA has already been performed.
   - Can you confirm the expected code changes were deployed?
   - Do permissions need to be rebuilt?
   - If all work from the issue is merged and the build was successful, you can move the issue to the done column on our Jira board and delete the feature branch from Github.
3. Repeat steps 1-2 to merge additional PRs until you've bundled all of the changes together that you want to go into the next "release" to Dev, Test, and Live.
4. Create a PR from `release` into `master` and click the "Merge" button (do not use "Squash and merge") when you're ready to deploy the release to Dev.
    - This merge triggers an automated CircleCI build, deployment, and test process on the Dev environment similar to the multidev deployment.
    - Verify that the CircleCI build on Dev is successful and passes our automated tests.

### Releases to Test (or Production)

We are using the Release environment to bundle all the approved code together into a single release which can then be deployed to Dev, Test, and Live and make sure things are solid. At least once per week, or more frequently as needed, our combined changes should be pushed to the Test and Live environments. The test deployment is essentially the last check to see if our code will be safe on Production and build correctly as the Pantheon Quicksilver scripts operate in a slightly different environment than CircleCI's Terminus commands.

1. Go to the Pantheon dashboard and navigate to the Test environment.
2. Under Deploys, you should see that the code you just deployed to Dev is ready for Test. Check the box to clone the Live database to Test and then merge that code and run the build on Test. You should make sure and provide a handy release message that tells us a little about what is included.
3. If necessary, go to the Test website and synchronize the configuration at /admin/config/development/configuration or run `lando terminus drush portlandor.test cim -y`.
4. Verify that everything still works on Test.

Once a deployment to Test has been tested and passes, the same set of changes should be promptly deployed to Production by following the same basic procedure above.

## Using Composer
Composer is built into our Lando implementation for package management. We use it primarily to manage Drupal contrib modules and libraries.
Here is a good guide to using Composer with Drupal 8: https://www.lullabot.com/articles/drupal-8-composer-best-practices
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
