# IP tracker

Simple php script to keep track of dynamic IPs, using mysql storage.

## Usage

`iptracker.php?set&site=mysite` stores the visible ip of the client accessing the script, and stores it under `mysite`.

`iptracker.php?site=mysite` fetches the last known ip for `mysite`. If no address is known nothing is returned.

## Configuration
Edit the first lines of the script, indicating the database configuration to use.
```php
$mysql_host = "";
$mysql_database = "";
$mysql_user = "";
$mysql_password = "";
```

The database must have a table create with the following command.
```sql
CREATE TABLE `ip` (
 `site` VARCHAR(256) CHARACTER SET ascii NOT NULL,
 `ip` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`site`)
);
```
