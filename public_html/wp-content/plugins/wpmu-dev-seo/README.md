# SmartCrawl SEO

Before starting development make sure you read and understand everything in this README.

## Working with Git

Clone the plugin repo and checkout the `release/x.x.x` (based on the vesion) branch

```
# git clone git@bitbucket.org:incsub/wpmu-dev-seo.git --recursive
# git fetch && git checkout release/x.x.x
```

Install/update the necessary submodules if the branch is already checked out

```
# git submodule init --
# git submodule update
```

Set up username and email for Git commits

```
# git config user.email "<your email>"
# git config user.name "<your name>"
```

## Installing dependencies and initial configuration

Install the necessary npm modules and packages

```
# npm install
```

##### Install Composer

Install composer following these steps - https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos

Install the necessary composer packages

```
# composer install
```

## Build tasks (npm)

Everything should be handled by npm. Note that you don't need to interact with Gulp in a direct way.

| Command              | Action                            |
|----------------------|-----------------------------------|
| `npm run watch`      | Watching css/js changes           |
| `npm run compile`    | Compile production ready assets   |                                          |
| `npm run build`      | Build both free and Pro versions. |
| `npm run build:pro`  | Build only pro version            |
| `npm run build:free` | Build only wp.org version         |
| `npm run translate`  | Create pot file.                  |
| `npm run test`       | Running tests                     |

**IMPORTANT!**

After branch checkout, you need to run `npm run compile` in order to build the assets
(minified versions of css and js files). Precompiled assets are not included with the development version of the plugin.
This is done so that the git commits are clean and do not include the built assets that are regenerated with every
change in the css/js files.

## Versioning

Follow semantic versioning [http://semver.org/](http://semver.org/) as `package.json` won't work otherwise. That's it:

-   `X.X.0` for mayor versions
-   `X.X.X` for minor versions
-   `X.X[.X||.0]-rc.1` for release candidates
-   `X.X[.X||.0]-beta.1` for betas (QA builds)
-   `X.X[.X||.0]-alpha.1` for alphas (design check tasks)

## Workflow

Do not commit on `master` branch (should always be synced with the latest released version). `release/x.x.x` is the code
that accumulates all the code for the next version.

-   Create a new branch from `release/x.x.x` branch: `git checkout -b branch-name`. Try to use the Jira task ID in branch name. For example:
	-   `new/SMA-123` for new features
	-   `improve/SMA-123` for enhancements
	-   `fix/SMA-123` for bug fixing
-   Make your commits and push the new branch: `git push -u origin branch-name`
-   File the new Pull Request against `release/x.x.x` branch.
-   Assign somebody to review your code.
-   Once the PR is approved, the lead dev will merge it to `release/x.x.x` branch.

It's a good idea to create the Pull Request as soon as possible so everybody knows what's going on with the project
from the PRs screen in Bitbucket.


## SmartCrawl release procedure

Before preparing a release make sure:
- You have merged all changes to the `master` branch.
- Master build succeeds on Bitbucket pipelines.
- Checkout `master` branch locally.
- Run `npm install` to install all required packages.
- Set correct version number in `package.json` file.
- Update `changelog.txt` file with all the changes in current relese.

### Releasing Pro Version

1. Run `npm run build:pro`, this will create a release ready package (wpmu-dev-seo-x.x.x.zip) in builds folder.
2. Upload this package to the [plugin release page](https://wpmudev.com/wp-admin/edit.php?post_type=project&page=projects-manage&manage_files=167).
3. Copy changelog entries from the `changelog.txt` to the release form.
4. Done.

### Releasing Free Version (wp.org)

1. Run `npm run build:free`, this will create a release ready package (smartcrawl-seo-x.x.x.zip) in builds folder.
2. Open the dir where you have the WP.org svn repo checked out
3. Under `tags` create a new dir for the version you want to release
4. Extract the free package (smartcrawl-seo-x.x.x.zip) created by build process into the new tag dir
5. Copy the WP.org `readme.txt` file from the trunk folder to this tag dir
6. Include the changelog entries from changelog.txt in WP.org `readme.txt`
7. Update the stable tag in WP.org `readme.txt`
8. Delete everything from the trunk dir and replace with the contents of the newly created tag dir
9. Push everything to SVN
10. Done.