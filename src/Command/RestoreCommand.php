<?php

namespace Nkt\BackupBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * Restores the backup for specified connection.
 * @author Gusakov Nikita <dev@nkt.me>
 */
class RestoreCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('doctrine:database:restore')
            ->setDescription('Restores a backup')
            ->addArgument('file', InputArgument::REQUIRED, 'The backup filename');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getConnection($input->getOption('connection'));
        $driver = $this->defineDriverName($connection->getDriver());
        $filename = $input->getArgument('file');
        if (preg_match('~(.+)\.gz$~', $filename, $matches) !== 0) {
            $output->writeln('<info>Start decompress backup...</info>');
            $process = new Process('gzip --decompress --keep ' . $filename);
            if (0 !== $exitCode = $process->run()) {
                $output->writeln(sprintf('<error>Decompress failed: %s!</error>', $process->getExitCodeText()));

                return $exitCode;
            }
            $filename = $matches[1];
        }
        switch ($driver) {
            case 'mysql':
                return $this->processRestore(sprintf(
                    'mysql --user="%s" --password="%s" --host="%s" --port="%s" %s < %s',
                    $connection->getUsername(),
                    $connection->getPassword(),
                    $connection->getHost(),
                    $connection->getPort(),
                    $connection->getDatabase(),
                    $filename
                ), $output);
            case 'pgsql':
                return $this->processRestore(sprintf(
                    'export PGPASSWORD=%s psql --username="%s" --host="%s" --port="%s" --dbname="%s" --file="%s"',
                    $connection->getPassword(),
                    $connection->getUsername(),
                    $connection->getHost(),
                    $connection->getPort(),
                    $connection->getDatabase(),
                    $filename
                ), $output);
            default:
                throw new UnsupportedException('Restore for "' . $driver . '" driver not supported');
        }
    }

    /**
     * @param string          $command The command string
     * @param OutputInterface $output  An output instance
     *
     * @return int Process exit code
     */
    private function processRestore($command, OutputInterface $output)
    {
        $process = new Process($command);
        $output->writeln('<info>Start restore database, it may take some time...</info>');
        $process->run(function ($type, $buff) use ($output) {
            $message = ($type === Process::ERR ? '<comment>%s</comment>' : '<info>%s</info>');
            $output->writeln(sprintf($message, trim($buff)));
        });
        if (0 === $exitCode = $process->getExitCode()) {
            $output->writeln(sprintf('<info>%s</info>', 'Restore completed!'));
        } else {
            $output->writeln(sprintf('<error>Restore failed: %s!</error>', $process->getExitCodeText()));
        }

        return $exitCode;
    }
}
