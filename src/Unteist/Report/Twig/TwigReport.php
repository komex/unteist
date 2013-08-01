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
     * Configure report generator.
     *
     * @param string $report_dir Report output directory
     */
    public function __construct($report_dir)
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . DIRECTORY_SEPARATOR . 'Templates');
        $this->twig = new \Twig_Environment($loader, ['cache' => sys_get_temp_dir()]);
        $this->output_dir = $report_dir;
        $fs = new Filesystem();
        if (!$fs->exists($this->output_dir)) {
            $fs->mkdir($this->output_dir);
        }
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
            EventStorage::EV_AFTER_TEST => 'onTestDone',
            EventStorage::EV_AFTER_CASE => 'onTestCaseDone',
        ];
    }

    /**
     * Generate TestCase report.
     *
     * @param TestCaseEvent $event TestCase information
     */
    public function onTestCaseDone(TestCaseEvent $event)
    {
        var_dump($this->twig->render('case.html.twig', ['case' => $event]));
    }

    /**
     * Generate Test report.
     *
     * @param TestEvent $event Test information
     */
    public function onTestDone(TestEvent $event)
    {
        var_dump($this->twig->render('test.html.twig', ['test' => $event]));
    }
}
