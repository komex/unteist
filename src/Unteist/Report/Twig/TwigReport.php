<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Report\Twig;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Report\Statistics\StatisticsProcessor;

/**
 * Class TwigReport
 *
 * @package Unteist\Report\Twig
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TwigReport implements EventSubscriberInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var string
     */
    protected $output_dir;
    /**
     * @var Filesystem
     */
    protected $fs;
    /**
     * @var StatisticsProcessor
     */
    protected $statistics;
    /**
     * @var \ArrayObject
     */
    protected $storage;

    /**
     * Configure report generator.
     *
     * @param string $report_dir Report output directory
     * @param array $alt_template_paths Paths to alternative templates.
     */
    public function __construct($report_dir, array $alt_template_paths = [])
    {
        array_unshift($alt_template_paths, __DIR__ . DIRECTORY_SEPARATOR . 'Templates');
        $loader = new \Twig_Loader_Filesystem($alt_template_paths);
        $this->twig = new \Twig_Environment($loader);
        $this->twig->addFunction(new \Twig_SimpleFunction('explode', 'explode'));
        $this->twig->addFunction(new \Twig_SimpleFunction('testPercent', [$this, 'getTestPercent']));
        $this->twig->addFilter(new \Twig_SimpleFilter('getPathByNamespace', [$this, 'getPathByNamespace']));
        $this->fs = new Filesystem();
        if (!$this->fs->exists($report_dir)) {
            $this->fs->mkdir($report_dir);
        }
        $this->output_dir = realpath($report_dir);
        $this->prepareReport();
        $this->statistics = new StatisticsProcessor();
        $this->storage = new \ArrayObject();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            EventStorage::EV_AFTER_CASE => 'onAfterTestCase',
            EventStorage::EV_APP_FINISHED => 'onAppFinished',
        ];
    }

    /**
     * Get percenf of specified status type.
     *
     * @param StatisticsProcessor $statistics
     * @param string $type Status type
     *
     * @return float
     */
    public function getTestPercent(StatisticsProcessor $statistics, $type)
    {
        $count = count($statistics);
        if ($count === 0 || !isset($statistics[$type])) {
            return 0;
        } else {
            $stat = $statistics[$type];
            if ($stat instanceof StatisticsProcessor) {
                $stat = count($stat);
            }

            return (($stat / $count) * 100);
        }
    }

    /**
     * Generate report index file.
     */
    public function onAppFinished()
    {
        $content = $this->twig->render(
            'index.html.twig',
            ['storage' => $this->storage, 'statistics' => $this->statistics, 'base_dir' => $this->output_dir]
        );
        file_put_contents($this->output_dir . DIRECTORY_SEPARATOR . 'index.html', $content);
    }

    /**
     * Generate TestCase report.
     *
     * @param TestCaseEvent $event TestCase information
     */
    public function onAfterTestCase(TestCaseEvent $event)
    {
        $statistics = new StatisticsProcessor($event);
        $this->statistics->addTestCaseEvent($event);
        $this->storage[$event->getClass()] = $statistics;
        $content = $this->twig->render(
            'case.html.twig',
            ['case' => $statistics, 'class' => $event->getClass(), 'base_dir' => $this->output_dir]
        );
        $path = $this->getPathByNamespace($event->getClass(), true);
        $this->fs->mkdir($path);
        file_put_contents($path . DIRECTORY_SEPARATOR . 'index.html', $content);
    }

    /**
     * Get path by class name (with namespace).
     *
     * @param string $namespace
     * @param bool $absolute
     *
     * @return string
     */
    public function getPathByNamespace($namespace, $absolute = false)
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        if ($absolute) {
            $path = $this->output_dir . DIRECTORY_SEPARATOR . $path;
        }

        return $path;
    }

    /**
     * Compile Bootstrap for reports.
     */
    private function prepareReport()
    {
        $css_dir = $this->getPath([$this->output_dir, 'css']);
        $vendor_dir = realpath('./vendor');
        $bootstrap_dir = $this->getPath([$vendor_dir, 'twitter', 'bootstrap']);
        $this->fs->mkdir($css_dir);
        $less = new \lessc();
        $less->setFormatter('compressed');
        $less->setImportDir($bootstrap_dir . DIRECTORY_SEPARATOR . 'less');
        $less->compileFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.less',
            $css_dir . DIRECTORY_SEPARATOR . 'bootstrap.min.css'
        );
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    private function getPath(array $parts)
    {
        $path = join(DIRECTORY_SEPARATOR, $parts);
        if (file_exists($path)) {
            return realpath($path);
        } else {
            return $path;
        }
    }
}
