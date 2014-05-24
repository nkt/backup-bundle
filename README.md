Image bundle
============
[![Latest Stable Version](https://poser.pugx.org/nkt/backup-bundle/v/stable.svg)](https://packagist.org/packages/nkt/backup-bundle) [![Total Downloads](https://poser.pugx.org/nkt/backup-bundle/downloads.svg)](https://packagist.org/packages/nkt/backup-bundle) [![Latest Unstable Version](https://poser.pugx.org/nkt/backup-bundle/v/unstable.svg)](https://packagist.org/packages/nkt/backup-bundle) [![License](https://poser.pugx.org/nkt/backup-bundle/license.svg)](https://packagist.org/packages/nkt/backup-bundle)

Usage
-----

Add `"nkt/backup-bundle": "1.0.x-dev"` into composer.json.

Add `Nkt\ImageBundle\NktImageBundle` into your kernel bundles.

Now you can add new cron job, for backup your application:

```bash
0 */6 * * * /path/to/app/console doctrine:database:backup --gzip=-9
```

You can also restore any backup using

```bash
/path/to/app/console doctrine:database:restore /path/to/app/backup/backupname.sql.gz
```

Backup options
--------------

Backup filename contains driver name, database name and date.
You can change date pattern (by default "Y-m-d-H-i-s")
using `--date-pattern` option.

If you using multiply connections, specify it using `--connection` option
or `-c` flag.

By default all backups saves into `path/to/app/backups`, you can change it
using `--destination` option or `-d` flag.

As you can see in example, you can compress backups using `--gzip` option.
This option required value, you have to specify compress quality.

Restore options
---------------

Restore command required filename by first argument. It also support
change connection with `--connection` option or `-c` flag.

You don't have to specify gzipped file. Command checks does filename
ends with `.gz` and decompress it.

License
-------

MIT
