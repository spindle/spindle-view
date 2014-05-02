<?php
/**
 * spindle/view
 * @license CC0-1.0 (Public Domain)
 */
namespace Spindle;

use ArrayObject;

/**
 * 素のPHPテンプレートにlayout機能を付加するシンプルなレンダラーです
 */
class View implements \IteratorAggregate
{
    protected
        /** @var ArrayObject */ $_storage
    ,   /** @var string */ $_basePath
    ,   /** @var string */ $_fileName
    ,   /** @var string */ $_layoutFileName = ''
    ,   /** @var string */ $_content = ''
    ;

    /**
     * @param string $fileName 描画したいテンプレートのファイル名を指定します
     * @param string $basePath テンプレートの探索基準パスです。相対パスも指定できます。指定しなければinclude_pathから探索します。
     * @param ArrayObject $arr
     */
    function __construct($fileName, $basePath='', ArrayObject $arr=null)
    {
        $this->_storage = $arr ?: new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        $this->_fileName = trim($fileName, \DIRECTORY_SEPARATOR);
        $this->_basePath = rtrim($basePath, \DIRECTORY_SEPARATOR);
    }

    /**
     * このクラスはforeach可能です
     * @return \ArrayIterator
     */
    function getIterator()
    {
        return $this->_storage->getIterator();
    }

    /**
     * @param string|int $name
     * @return mixed
     */
    function __get($name)
    {
        return $this->_storage[$name];
    }

    /**
     * @param string|int $name
     * @param mixed $value
     */
    function __set($name, $value)
    {
        $this->_storage[$name] = $value;
    }

    /**
     * @param string|int $name
     * @return bool
     */
    function __isset($name)
    {
        return isset($this->_storage[$name]);
    }

    /**
     * 描画するスクリプトファイルのパスを返します。
     * @return string
     */
    function __toString()
    {
        if ($this->_basePath) {
            return $this->_basePath . \DIRECTORY_SEPARATOR . $this->_fileName;
        } else {
            return (string)$this->_fileName;
        }
    }

    /**
     * セットされたview 変数を配列化して返します
     * @return array
     */
    function toArray()
    {
        return (array)$this->_storage;
    }

    /**
     * 配列で一気にview変数をセットします
     * @param array|\Traversable $array
     */
    function assign($array)
    {
        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new \InvalidArgumentException('$array must be array or Traversable.');
        }

        foreach ($array as $key => $value) {
            $this->_storage[$key] = $value;
        }
    }

    /**
     * @param string $name
     * @param array $array
     */
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

    /**
     * テンプレートファイルを描画して文字列にして返します
     * @return string
     */
    function render()
    {
        foreach ($this->_storage as ${"\x00key"} => ${"\x00val"}) {
            $${"\x00key"} = ${"\x00val"};
        }
        ob_start();
        include (string)$this;
        $html = ob_get_clean();

        if ($this->_layoutFileName) {
            $layout = new static(
                $this->_layoutFileName,
                $this->_basePath,
                $this->_storage
            );
            $layout->setContent($html);
            return $layout->render();
        } else {
            return $html;
        }
    }

    /**
     * @internal
     */
    protected function setContent($content)
    {
        $this->_content = $content;
    }

    /**
     * 子テンプレートの描画結果を文字列として返します
     * @return string
     */
    function content()
    {
        return $this->_content;
    }

    /**
     * 親となるレイアウトテンプレートのファイル名を指定します。
     * レイアウトは__construct()で指定した基準パスと同じパスから探索します。
     * @param string $layoutFileName
     */
    function setLayout($layoutFileName)
    {
        $this->_layoutFileName = $layoutFileName;
    }

    /**
     * 現在セットされているレイアウトファイル名を返します
     * @return string
     */
    function getLayout()
    {
        return $this->_layoutFileName;
    }

    /**
     * 指定したテンプレートファイルを描画します。
     * 変数は引き継がれます。
     * @param string $partialFileName
     * @return string
     */
    function partial($partialFileName)
    {
        $partial = new static(
            $partialFileName,
            $this->_basePath,
            $this->_storage
        );
        return $partial->render();
    }
}
