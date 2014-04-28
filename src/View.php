<?php
/**
 * spindle/view
 *
 * @license CC0 (Public Domain) see LICENSE.md
 */
namespace Spindle;

/**
 *
 *
 */
class View implements \IteratorAggregate
{
    static $extract = false;

    protected
        $_storage
    ,   $_basePath
    ,   $_fileName
    ,   $_layoutFileName = ''
    ,   $_content = ''
    ;

    function __construct($fileName, $basePath = '')
    {
        $this->_storage = new \ArrayObject(array(), \ArrayObject::ARRAY_AS_PROPS);
        $this->_fileName = trim($fileName, \DIRECTORY_SEPARATOR);
        $this->_basePath = rtrim($basePath, \DIRECTORY_SEPARATOR);
    }

    function getIterator()
    {
        return $this->_storage->getIterator();
    }

    function __get($name)
    {
        return $this->_storage[$name];
    }

    function __set($name, $value)
    {
        $this->_storage[$name] = $value;
    }

    function __isset($name)
    {
        return isset($this->_storage[$name]);
    }

    function __toString()
    {
        return $this->_basePath . \DIRECTORY_SEPARATOR . $this->_fileName;
    }

    function toArray()
    {
        return (array)$this->_storage;
    }

    function assign($array)
    {
        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new \InvalidArgumentException('$array must be array or Traversable.');
        }

        foreach ($array as $key => $value) {
            $this->_storage[$key] = $value;
        }
    }

    function append($name, $array)
    {
        $s = $this->_storage;
        if (isset($s[$name])) {
            $s[$name] = array_merge((array)$s[$name], (array)$array);
        } else {
            $s[$name] = (array)$array;
        }
    }

    function prepend($name, $array)
    {
        $s = $this->_storage;
        if (isset($s[$name])) {
            $s[$name] = array_merge((array)$array, (array)$s[$name]);
        } else {
            $s[$name] = (array)$array;
        }
    }

    function render()
    {
        ob_start();
        include $this->_basePath . \DIRECTORY_SEPARATOR . $this->_fileName;
        $html = ob_get_clean();

        if ($this->_layoutFileName) {
            $layout = new static($this->_layoutFileName, $this->_basePath);
            $layout->_storage = $this->_storage;
            $layout->setContent($html);
            return $layout->render();
        } else {
            return $html;
        }
    }

    protected function setContent($content)
    {
        $this->_content = $content;
    }

    function content()
    {
        return $this->_content;
    }

    function setLayout($layoutFileName)
    {
        $this->_layoutFileName = $layoutFileName;
    }

    function getLayout()
    {
        return $this->_layoutFileName;
    }

    function partial($partialFileName)
    {
        $partial = new static($partialFileName, $this->_basePath);
        $partial->_storage = $this->_storage;
        return $partial->render();
    }
}
