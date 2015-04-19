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
     * @param ArrayObject $arr view変数の引き継ぎ元です。内部用なので通常は使う必要はありません。
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
        $this->_merge($name, (array)$array, true);
    }

    /**
     * @param string $name
     * @param array $array
     */
    function prepend($name, $array)
    {
        $this->_merge($name, (array)$array, false);
    }

    /**
     * @param string $name
     * @param array $array
     * @param bool  $append
     */
    private function _merge($name, array $array, $append=true)
    {
        $s = $this->_storage;
        if (isset($s[$name])) {
            if ($append) {
                $s[$name] = array_merge((array)$s[$name], $array);
            } else {
                $s[$name] = array_merge($array, (array)$s[$name]);
            }
        } else {
            $s[$name] = $array;
        }
    }

    /**
     * テンプレートファイルを描画して文字列にして返します
     * テンプレート内では$EでHTMLエスケープでき、
     * <?= $E('<script>') ?> と <?= htmlspecialchars('<script>') ?> が等価です。
     *
     * @return string
     */
    function render()
    {
        $E = get_class($this) . '::h';
        extract((array)$this->_storage, \EXTR_OVERWRITE);
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
     * @param string $content
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

    /**
     * htmlspecialchars()のエイリアスです。
     *
     * @param string $rawStr 
     * @param int $mode
     * @param string $charset 文字コードです。指定しなければdefault_charsetの値を使用します。
     * @return string
     */
    static function h($rawStr, $mode=\ENT_QUOTES, $charset=null)
    {
        static $solvedCharset = null;
        if (!$solvedCharset || $charset) {
            $solvedCharset = (string)$charset ?: ini_get('default_charset') ?: 'UTF-8';
        }
        return \htmlspecialchars($rawStr, $mode, $solvedCharset);
    }

    /**
     * metaタグ群を出力するためのヘルパー関数です。
     * <?= self::meta($meta) ?>
     *
     * @example
     *  <pre>
     *   $meta = [
     *     'charset' => 'utf8',
     *     'http-equiv' => [
     *       'X-UA-Compatible' => 'IE=edge',
     *       'Content-Type' => 'text/html; charset=UTF-8',
     *     ],
     *     'name' => [
     *       'keyword' => 'hoge,fuga,muga',
     *       'description' => 'hogehoge',
     *       'twitter:title' => 'Mountain sunset',
     *     ],
     *     'property' => [
     *       'og:title' => 'foooooo',
     *       'og:description' => 'hogehoge',
     *       'og:image' => [
     *         'http://hoge.com/hoge1.jpg',
     *         'http://hoge.com/hoge2.jpg',
     *       ],
     *     ],
     *   ];
     *  </pre>
     *
     * @param array|Traversable $meta metaタグの定義。
     * @param boolean $isXhtml trueを指定すると、タグがXHTML形式になります。
     * @param boolean $escape  falseを指定すると、HTMLエスケープを行わなくなります。
     * @return string
     */
    static function meta($meta, $isXhtml=false, $escape=true)
    {
        if (!is_array($meta) && !is_object($meta)) {
            throw new InvalidArgumentException('$meta should be Traversable or array. ' . gettype($meta). ' passed.');
        }

        $html = array();

        $TAGEND = $isXhtml ? ' />' : '>';
        $escape = (bool)$escape;

        foreach ($meta as $attr => $defs) {
            $attr = $escape ? static::h($attr, \ENT_COMPAT) : $attr;

            if (is_array($defs) || is_object($defs)) {
                foreach ($defs as $label => $val) {
                    $label = $escape ? static::h($label, \ENT_COMPAT) : $label;
                    if (is_array($val) || is_object($val)) {
                        foreach ($val as $v) {
                            $html[] = self::_generateMeta($attr, $label, $TAGEND, $escape, $v);
                        }
                    } else {
                        $html[] = self::_generateMeta($attr, $label, $TAGEND, $escape, $val);
                    }
                }
            } else {
                $html[] = self::_generateMeta($attr, $label, $TAGEND, $escape);
            }
        }

        return implode(PHP_EOL, $html);
    }

    private static function _generateMeta($type, $val, $TAGEND, $escape, $content=null)
    {
        if ($content) {
            $content = $escape ? static::h($content, \ENT_COMPAT) : $content;
            return '<meta ' . $type . '="' . $val . '" content="' . $content . '"' . $TAGEND;
        } else {
            $val = $escape ? static::h($val, \ENT_COMPAT) : $val;
            return '<meta ' . $type . '="' . $val . '"' . $TAGEND;
        }
    }
}
