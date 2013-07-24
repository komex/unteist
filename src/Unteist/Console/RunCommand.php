<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Console;


use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Unteist\Filter\ClassFilter;
use Unteist\Filter\MethodsFilter;
use Unteist\Processor\Processor;
use Unteist\Strategy\Context;

/**
 * Class RunCommand
 *
 * @package Unteist\Console
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class RunCommand extends Command
{
    /**
     * @var array
     */
    protected $log_levels = [
        'DEBUG' => Logger::DEBUG,
        'INFO' => Logger::INFO,
        'NOTICE' => Logger::NOTICE,
        'WARNING' => Logger::WARNING,
        'ERROR' => Logger::ERROR,
        'CRITICAL' => Logger::CRITICAL,
    ];

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('run')->setDescription('Run tests stored in specified directory.');
        $this->addArgument('source', InputArgument::REQUIRED, 'Path to TestCase classes.');
        $this->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Run test in N separated processes.', 1);
        $this->addOption('strategy', 's', InputOption::VALUE_REQUIRED, 'Assert strategy: STOP, IGNORE', 'STOP');
        $this->addOption(
            'log-level',
            'l',
            InputOption::VALUE_REQUIRED,
            sprintf('Logger level: OFF, %s.', join(', ', array_keys($this->log_levels))),
            'OFF'
        );
        $this->addOption('log-file', 'f', InputOption::VALUE_REQUIRED, 'Logger output file.', 'unteist.log');
    }

    /**
     * Start searching and executing tests.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new Logger('unteist');
        if (isset($this->log_levels[$input->getOption('log-level')])) {
            $logger->pushHandler(
                new StreamHandler($input->getOption('log-file'), $this->log_levels[$input->getOption('log-level')])
            );
        } else {
            $logger->pushHandler(new NullHandler());
        }
        $finder = new Finder();
        $finder->files()->in($input->getArgument('source') ? : __DIR__)->name('*Test.php');
        $event_dispatcher = new EventDispatcher();
        $processor = new Processor($event_dispatcher, $logger);
        $processor->addClassFilter(new ClassFilter());
        $processor->addMethodsFilter(new MethodsFilter());
        $processor->setSuites($finder);
        $processor->setMaxProcs($input->getOption('processes'));
        switch ($input->getOption('strategy')) {
            case 'IGNORE':
                $processor->setStrategy(Context::STRATEGY_IGNORE_FAILS);
                break;
            default:
                $processor->setStrategy(Context::STRATEGY_STOP_ON_FAILS);
        }

        return $processor->run();
    }

}