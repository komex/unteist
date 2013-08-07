<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 * @package Unteist\Configuration
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Configuration
{
    /**
     * @var Processor
     */
    protected $processor;
    /**
     * @var ConfigurationValidator
     */
    protected $validator;
    /**
     * @var array
     */
    protected $configs = [];
    /**
     * @var array
     */
    protected $result = [];

    /**
     * Prepare configuration loader.
     */
    public function __construct()
    {
        $this->processor = new Processor();
        $this->validator = new ConfigurationValidator();
    }

    /**
     * Load config from specified file.
     *
     * @param string $file Filename
     */
    public function getFromYaml($file)
    {
        $config = Yaml::parse($file);
        if (is_array($config)) {
            array_push($this->configs, $config);
        }
    }

    /**
     * Load config from Console Input.
     *
     * @param InputInterface $input
     */
    public function getFromInput(InputInterface $input)
    {
        $config = [];
        $processes = $input->getOption('processes');
        if ($processes !== null) {
            $config['processes'] = $processes;
        }
        $report_dir = $input->getOption('report-dir');
        if ($report_dir !== null) {
            $config['report_dir'] = $report_dir;
        }
        $log_level = $input->getOption('log-level');
        if ($log_level !== null) {
            if (empty($config['logger'])) {
                $config['logger'] = [];
            }
            $config['logger']['level'] = $log_level;
        }
        array_push($this->configs, $config);
    }

    /**
     * Process all configuration.
     *
     * @return array
     */
    public function processConfiguration()
    {
        $this->result = $this->processor->processConfiguration($this->validator, $this->configs);
    }
}
