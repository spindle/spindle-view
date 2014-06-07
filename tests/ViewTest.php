<?php
namespace Spindle\View\Tests;

use Spindle\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    private $view;
    private $fullpath;

    function setup()
    {
        $this->view = new View('template.phtml', __DIR__ . '/../example/01_simple/');
        $this->fullpath = __DIR__ . '/../example/01_simple/template.phtml';
    }

    /**
     * @test
     */
    function objectInitialize()
    {
        $view = new View('template.phtml', 'example/01_simple/');
        self::assertInstanceOf('Spindle\View', $view);

        $view = new View('template.phtml', 'example/01_simple/', new \ArrayObject);
        self::assertInstanceOf('Spindle\View', $view);
    }

    /**
     * @test
     */
    function magicMethods()
    {
        $view = $this->view;
        $view->a = 1;
        $view->b = 2;
        self::assertEquals(1, $view->a);
        self::assertEquals(2, $view->b);
        self::assertTrue(isset($view->a));
        self::assertEquals(array('a'=>1, 'b'=>2), iterator_to_array($view));
        self::assertEquals(array('a'=>1, 'b'=>2), $view->toArray());

        self::assertEquals($this->fullpath, (string)$view);

        $view = new View('a');
        self::assertEquals('a', (string)$view);
    }

    /**
     * @test
     */
    function assign1()
    {
        $view = $this->view;
        $a = 1;
        $b = 2;
        $view->assign(compact('a', 'b'));

        self::assertEquals(array('a'=>1, 'b'=>2), $view->toArray());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function assign2()
    {
        $this->view->assign(1); //not foreachable
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function assign3()
    {
        $this->view->assign(new \stdClass);
    }

    /**
     * @test
     */
    function append()
    {
        $view = $this->view;
        $view->append('arr', array(1, 2));
        self::assertEquals(array(1, 2), $view->arr);

        $view->append('arr', array(3, 4));
        self::assertEquals(array(1, 2, 3, 4), $view->arr);
    }

    /**
     * @test
     */
    function prepend()
    {
        $view = $this->view;
        $view->prepend('arr', array(1, 2));
        self::assertEquals(array(1, 2), $view->arr);

        $view->prepend('arr', array(3, 4));
        self::assertEquals(array(3, 4, 1, 2), $view->arr);
    }

    /**
     * @test
     */
    function layout()
    {
        $view = new View('template.phtml', __DIR__ . '/../example/04_layout2/');
        $view->title = 'example 4';
        $view->data = range(1, 4);

        $view->setLayout('layout.phtml');
        self::assertEquals('layout.phtml', $view->getLayout());

        self::assertEquals(
            file_get_contents(__DIR__ . '/../example/04_layout2/result.html'),
            $view->render()
        );
    }

    /**
     * @test
     */
    function partial()
    {
        $view = new View('template.phtml', __DIR__ . '/../example/05_partial/');
        $view->title = 'example 5';
        $view->data = range(1, 4);

        self::assertEquals(
            file_get_contents(__DIR__ . '/../example/05_partial/result.html'),
            $view->render()
        );
    }
}
