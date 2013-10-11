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
use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Report\Statistics\ClassStatistics;

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
     * @var ClassStatistics
     */
    protected $statistics;
    /**
     * @var \ArrayObject[]
     */
    protected $storage = [];

    /**
     * Configure report generator.
     *
     * @param string $report_dir Report output directory
     * @param array $alt_template_paths Paths to alternative templates.
     */
    public function __construct($report_dir, array $alt_template_paths = [])
    {
        array_push($alt_template_paths, __DIR__ . DIRECTORY_SEPARATOR . 'Templates');
        $loader = new \Twig_Loader_Filesystem($alt_template_paths);
        $this->twig = new \Twig_Environment($loader);
        $this->twig->addFunction(new \Twig_SimpleFunction('explode', 'explode'));
        $this->twig->addFunction(new \Twig_SimpleFunction('levelUp', [$this, 'levelUp']));
        $this->twig->addFunction(new \Twig_SimpleFunction('getTestPercent', [$this, 'getTestPercent']));
        $this->twig->addFilter(new \Twig_SimpleFilter('getPathByNamespace', [$this, 'getPathByNamespace']));
        $this->fs = new Filesystem();
        if (!$this->fs->exists($report_dir)) {
            $this->fs->mkdir($report_dir);
        }
        $this->output_dir = realpath($report_dir);
        $this->statistics = new ClassStatistics();
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
            EventStorage::EV_METHOD_DONE => 'methodFinish',
            EventStorage::EV_METHOD_FAILED => 'methodFinish',
            EventStorage::EV_METHOD_SKIPPED => 'methodFinish',
            EventStorage::EV_METHOD_INCOMPLETE => 'methodFinish',
            EventStorage::EV_AFTER_CASE => 'onAfterTestCase',
            EventStorage::EV_APP_FINISHED => 'onAppFinished',
        ];
    }

    /**
     * Attach method information to storage.
     *
     * @param MethodEvent $event
     */
    public function methodFinish(MethodEvent $event)
    {
        if (empty($this->storage[$event->getClass()])) {
            $this->storage[$event->getClass()] = new \ArrayObject();
        }
        $this->storage[$event->getClass()]->append($event);
    }

    /**
     * Generate TestCase report.
     *
     * @param TestCaseEvent $event TestCase information
     */
    public function onAfterTestCase(TestCaseEvent $event)
    {
        $methods = $this->storage[$event->getClass()];
        $statistics = new ClassStatistics();
        $statistics->addEvents($methods);
        $this->statistics->addEvents($methods);
        $this->statistics->addStatistics($event, $statistics);
        $content = $this->twig->render(
            'case.html.twig',
            ['case' => $methods, 'event' => $event, 'statistics' => $statistics]
        );
        $path = $this->getPathByNamespace($event->getClass(), true);
        $this->fs->mkdir($path);
        file_put_contents($path . DIRECTORY_SEPARATOR . 'index.html', $content);
    }

    /**
     * Generate report index file.
     */
    public function onAppFinished()
    {
        $content = $this->twig->render(
            'index.html.twig',
            ['statistics' => $this->statistics]
        );
        file_put_contents($this->output_dir . DIRECTORY_SEPARATOR . 'index.html', $content);
    }

    /**
     * Get relative path.
     *
     * @param string $namespace
     *
     * @return string
     */
    public function levelUp($namespace)
    {
        if (empty($namespace)) {
            return '';
        } else {
            return $this->fs->makePathRelative($this->output_dir, $this->getPathByNamespace($namespace, true));
        }
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
     * Count percents.
     *
     * @param int $count
     * @param int $total
     *
     * @return float
     */
    public function getTestPercent($count, $total)
    {
        if ($total === 0) {
            return 0;
        } else {
            return (($count / $total) * 100);
        }
    }
}
