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
use Unteist\Event\TestEvent;

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
     * Configure report generator.
     *
     * @param string $report_dir Report output directory
     */
    public function __construct($report_dir)
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . DIRECTORY_SEPARATOR . 'Templates');
        $this->twig = new \Twig_Environment($loader);
        $this->twig->addFunction(new \Twig_SimpleFunction('explode', 'explode'));
        $this->twig->addFilter(new \Twig_SimpleFilter('getPathByNamespace', [$this, 'getPathByNamespace']));
        $this->fs = new Filesystem();
        if (!$this->fs->exists($report_dir)) {
            $this->fs->mkdir($report_dir);
        }
        $this->output_dir = realpath($report_dir);
        $this->compileBootstrap();
    }

    /**
     * Compile Bootstrap for reports.
     */
    protected function compileBootstrap()
    {
        $css_dir = $this->output_dir . DIRECTORY_SEPARATOR . 'css';
        $bootstrap_dir = realpath(
            join(
                DIRECTORY_SEPARATOR,
                [__DIR__, '..', '..', '..', '..', 'vendor', 'twitter', 'bootstrap', 'less']
            )
        );
        $this->fs->mkdir($css_dir);
        $less = new \lessc();
        $less->setFormatter('compressed');
        $less->setImportDir($bootstrap_dir);
        $less->compileFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.less',
            $css_dir . DIRECTORY_SEPARATOR . 'bootstrap.min.css'
        );
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
            EventStorage::EV_BEFORE_CASE => 'onBeforeTestCase',
            EventStorage::EV_AFTER_TEST => 'onAfterTest',
            EventStorage::EV_AFTER_CASE => 'onAfterTestCase',
        ];
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
     * Create directory before TestCase start.
     *
     * @param TestCaseEvent $event
     */
    public function onBeforeTestCase(TestCaseEvent $event)
    {
        $path = $this->getPathByNamespace($event->getClass(), true);
        $this->fs->mkdir($path);
    }

    /**
     * Generate TestCase report.
     *
     * @param TestCaseEvent $event TestCase information
     */
    public function onAfterTestCase(TestCaseEvent $event)
    {
        $content = $this->twig->render('case.html.twig', ['case' => $event, 'base_dir' => $this->output_dir]);
        $path = $this->getPathByNamespace($event->getClass(), true);
        file_put_contents($path . DIRECTORY_SEPARATOR . 'index.html', $content);
    }

    /**
     * Generate Test report.
     *
     * @param TestEvent $event Test information
     */
    public function onAfterTest(TestEvent $event)
    {
        $content = $this->twig->render('test.html.twig', ['test' => $event, 'base_dir' => $this->output_dir]);
        $class = $event->getTestCaseEvent()->getClass();
        $path = $this->getPathByNamespace($class, true);
        $data_set = $event->getDataSet();
        if (empty($data_set)) {
            file_put_contents($path . DIRECTORY_SEPARATOR . $event->getMethod() . '.html', $content);
        } else {
            //@todo: Доделать отчеты с dataProvider
        }
    }
}
