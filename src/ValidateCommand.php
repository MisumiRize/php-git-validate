<?php

namespace Lethe\GitValidate;

use Phine\Path\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ValidateCommand extends Command
{
    protected function configure()
    {
        $this->setName('validate')
            ->setDescription('Run commit hook validation')
            ->addArgument('hook', InputArgument::REQUIRED, 'When to validate')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Config json file', '.validate.json');
    }

    private function readConfig($path)
    {
        try {
            $file = new \SplFileObject($path);
            $config = ($file->isReadable()) ? json_decode($this->readContent($file)) : new \stdClass();
        } catch (\RuntimeException $e) {
            $config = new \stdClass();
        }

        if (!is_object($config)) {
            throw new \RuntimeException('invalid json');
        }

        return $config;
    }

    private function readContent(\SplFileObject $file)
    {
        if (method_exists($file, 'fread')) {
            return $file->fread($file->getSize());
        }

        $content = [];
        while (!$file->eof) {
            $content[] = $file->fgets();
        }

        return implode("\n", $content);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = Installation::findGitRoot();
        $path = $input->getOption('config');
        if (Path::isLocal($path)) {
            $path = Path::join([$project, $path]);
        }

        $config = $this->readConfig($path);

        $hook = $input->getArgument('hook');
        $commands = isset($config->{$hook}) ? $config->{$hook} : [];
        if (is_object($commands)) {
            $commands = (array) $commands;
        } elseif (is_string($commands)) {
            $commands = [$commands];
        } elseif (!is_array($commands)) {
            throw new \RuntimeException('"'.$hook.'" key in '.$path.' should be array/hash/string value');
        }

        if (count($commands)) {
            $output->writeln(sprintf('running %s checks...', $hook));
        }

        $composer = $this->readConfig(Path::join([$project, 'composer.json']));

        $scripts = array_reduce([$composer, $config], function ($carry, $c) {
            $scripts = isset($c->scripts) ? $c->scripts : new \stdClass();

            if (!is_object($scripts)) {
                throw new \RuntimeException('"scripts" key in config should be hash value');
            }

            return array_merge($carry, (array) $scripts);
        }, []);

        $env = array_merge($_SERVER,
            [
                'PATH' => Path::join([$project, 'vendor', 'bin']).PATH_SEPARATOR.getEnv('PATH')
            ]);

        $tasks = array_map(function ($command) use ($scripts) {
            return [
                'command' => $command,
                'script' => isset($scripts[$command]) ? $scripts[$command] : '',
            ];
        }, $commands);

        foreach ($tasks as $task) {
            if (empty($task['script'])) {
                $output->writeln('running '.$task['command'].': <comment>n/a</comment> (no script found)');
                continue;
            }

            $process = new Process('sh -c "'.$task['script'].'"', null, $env);
            $output->write('running '.$task['command'].': ');
            $process->run();

            if ($process->isSuccessful()) {
                $output->writeln('<info>ok</info>');
            } else {
                $output->writeln('<error>failed!</error>');
                $output->writeln($process->getOutput());
                $output->writeln($process->getErrorOutput());

                return $process->getExitCode();
            }
        }

        return 0;
    }
}
