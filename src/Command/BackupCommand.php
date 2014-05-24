<?php

namespace Nkt\BackupBundle\Command;

use Nkt\BackupBundle\Exception\UnsupportedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Creates the backup for specified connection.
 * @author Gusakov Nikita <dev@nkt.me>
 */
class BackupCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('doctrine:database:backup')
            ->setDescription('Backup all your database data')
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'Destination directory relative to kernel root', 'backups')
            ->addOption('date-pattern', null, InputOption::VALUE_REQUIRED, 'The date pattern for backup filename', 'Y-m-d-H-i-s')
            ->addOption('gzip', null, InputOption::VALUE_REQUIRED, 'Enable gzip compressing, value provides compress level', false);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $connection = $this->getConnection($input->getOption('connection'));
        $driver = $this->defineDriverName($connection->getDriver());

        $dir = $this->getContainer()->getParameter('kernel.root_dir') . '/' . trim($input->getOption('destination'), '/');
        $filesystem->mkdir($dir);

        $filename = $dir . '/' . $driver . '.' . $connection->getDatabase() . '.' . date($input->getOption('date-pattern')) . '.sql';
        $gzip = $input->getOption('gzip');
        switch ($driver) {
            case 'mysql':
                return $this->processBackup(sprintf(
                    'mysqldump --user="%s" --password="%s" --host="%s" --port="%s" %s',
                    $connection->getUsername(),
                    $connection->getPassword(),
                    $connection->getHost(),
                    $connection->getPort(),
                    $connection->getDatabase()
                ), $filename, $gzip, $output);
            case 'pgsql':
                return $this->processBackup(sprintf(
                    'export PGPASSWORD=%s pg_dump --username="%s" --host="%s" --port="%s" --dbname="%s"',
                    $connection->getPassword(),
                    $connection->getUsername(),
                    $connection->getHost(),
                    $connection->getPort(),
                    $connection->getDatabase(),
                    $filename
                ), $filename, $gzip, $output);
            default:
                throw new UnsupportedException('Backup for "' . $driver . '" driver not supported');
        }
    }

    /**
     * @param string          $command The command string
     * @param string          $filename
     * @param bool|string     $gzip
     * @param OutputInterface $output  An output instance
     *
     * @return int Process exit code
     */
    private function processBackup($command, $filename, $gzip, OutputInterface $output)
    {
        if ($gzip !== false) {
            $command .= ' | gzip > ' . $filename . '.gz';
        } else {
            $command .= ' > ' . $filename;
        }
        $process = new Process($command);
        $output->writeln('<info>Start backup database, it may take some time...</info>');
        $process->run(function ($type, $buff) use ($output) {
            $message = ($type === Process::ERR ? '<comment>%s</comment>' : '<info>%s</info>');
            $output->writeln(sprintf($message, trim($buff)));
        });
        if (0 === $exitCode = $process->getExitCode()) {
            $output->writeln(sprintf('<info>%s</info>', 'Backup completed!'));
        } else {
            $output->writeln(sprintf('<error>Backup failed: %s!</error>', $process->getExitCodeText()));
        }

        return $exitCode;
    }
}
