# Employees Migrations

A module for migrating content to employees.portland.gov.

## Instructions

- Verify that the CSV files are in the proper location. See instructions below.
- Enable the Employees Migrations (`employees_migrations`) module.
- Run migrations using drush. The commands for running each migration are listed below. In most cases it is imperative to run the migrations in the order listed due to interdependencies.
- After running the migrations, visit /admin/content to view the imported content.

### Running migrations

The Migrate Tools module provides drush commands to run the migrations. The order of commands is important! When running the migrations on remote servers, such as multidev or Dev/Test/Live, use the terminus commands. For multidev environments, the environment id is formatted like this: employees.powr-1234. Example:

```
lando terminus drush [environment] -- migrate:import [migration_id]
```

Some migrations have interdependencies, such as employee_news, employee_news_group_relationship, and employee_news_redirects. You can automatically run all migrations in a group with:

```
lando terminus drush [environment] -- migrate:import --group=[migration_group]
```

After editing an existing migration, you need to run `lando drush cr` before it will pick up the changes.

If the migration reports that any items failed, you can see what the failed items were with `lando drush mmsg`

### Rolling back migrations

To roll back a migration, use the `migrate:rollback [migration_name]` command, and roll back migrations in the reverse order than they were originally rolled.

Some migrations have interdependencies, such as employee_news, employee_news_group_relationship, and employee_news_redirects. Interdependent migrations must all be rolled back together. This can be done automatically with:

```
lando terminus drush [environment] migrate:rollback --group=[migration_group]
```

### Timeouts

Long migrations run through terminus may exceed the Pantheon timeout and be terminated with a message such as, "Connection to appserver.powr-1284.5c6715db-abac-4633-ada8-1c9efe354629.drush.in closed by remote host." There is no way to increase this timeout, but a workaround exists. The migration may be reset and restarted. It will pick up where it left off. This may need to be done multiple times for long migrations.

Use the following commands to reset and restart the migration:

```
lando terminus drush employees.powr-[ID] -- migrate:reset-status [migration_id]
lando terminus drush employees.powr-[ID] -- migrate:import [migration_id]
```

You can also check the status of the migration using the `migrate:status` command:

```
lando terminus drush employees.powr-[ID] -- migrate:status
```

## CSV files

The `path` configuration for the CSV source plugin accepts an absolute path or relative path from the Drupal installation folder.

The examples use a relative path and it is assumed that you place this module in a `modules/custom` folder. Therefore, the CSV files will be located at `modules/custom/employees_migrations/sources/`.

Not having the source CSV files in the proper location would trigger and error similar to:

```
[error]  Migration failed with source plugin exception: File path (modules/custom/employees_migrations/sources/employee_news.csv) does not exist.
```

If you want to place the files in a different location, you need to update the path in the corresponding configuration files. That is the `source:path` setting in the migration files.

### CSV files manual modifications

For some of the content migrations, the exported data may have been massaged to avoid complex migration routines. The necessary updates are described in the migrations sections below.

Windows users: When manually editing CSV files beware of file encodings. UTF-8 is the standard encoding, but Excel exports a CSV with the encoding "UTF-8 with BOM" which can cause errors during the import such as:

```
In Condition.php line 105:
Query condition 'taxonomy_term_field_data.name IN ()' cannot be empty.
```

### Python processing

Some migrations might have Python scripts that perform post processing on the raw CSV exported from POG to generate the actual CSV used by the migration script.

#### Instructions to install and run Python in Lando

```
lando ssh -u root
apt update
apt install python3
apt install python3-pip
pip3 install pandas
pip3 install bs4
cd web/modules/custom/employees_migrations/sources/
python3 [script_name.py]
```

## Migrations

In addition to the primary content migrations, there are two supplemental migrations that are run for some content types: redirects and group_relationship.

Redirects migrations write entities to the redirects table. This is used for creating the legacy paths functionality, where the path from POG is linked to the corresponding page in the new site. Redirects migrations are named with the suffix "_redirects." *Example: employees_news_redirects*

Group content migrations are used to add content to a group by creating a group content entity. These migrations are named with the suffix "_group_relationship." *Example: employees_news_group_relationship*

### Migrations in this module

- [your migration name]

#### Citywide Contracts
##### Local
```
lando drush migrate:import citywide_contracts
```
##### On Pantheon
```
lando terminus drush employees.[env] -- migrate:import citywide_contracts
```

#### Taxonomy Import of Missing Topics and Tags
##### Local
```
lando drush migrate:import --group=taxonomy_import
```
##### On Pantheon
```
lando terminus drush employees.[env] -- migrate:import --group=taxonomy_import
```

#### BTS Technology Catalog
##### Local
```
lando drush migrate:import --group=tech_catalog
```
##### On Pantheon
```
lando terminus drush employees.[env] -- migrate:import --group=tech_catalog
```

#### BTS Blog
##### Local
```
lando drush migrate:import --group=bts_blog
```
##### On Pantheon
```
lando terminus drush employees.[env] -- migrate:import --group=bts_blog
```
