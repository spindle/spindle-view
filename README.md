spindle/view
=========================

素のPHPをテンプレートエンジンとして使う際、レイアウト構造を実現するライブラリです。
一つのクラスだけの非常に小さなライブラリですが、"継承"機能をほぼ網羅しています。

エスケープなどの機能はありません。

```sh
$ composer require spindle/view:*
```

使い方
-------------------------
クラス一つだけなので、require\_onceで読み込むこともできますし、composerでオートロードしても構いません。

### 1. シンプルな例

`template.phtml`は素のPHPで書かれたテンプレートファイルとします。
$titleと$mainを埋めるとHTMLの文字列が完成します。

```php
<html>
<head>
 <title><?php echo $title ?></title>
</head>
<body>
 <h1><?php echo $title ?></h1>
 <div class="main">
  <?php echo $main ?> 
 </div>
</body>
</html>
```

これをHTML化するには、以下のようなスクリプトを書きます。

```php
<?php
require 'vendor/autoload.php';

$view = new Spindle\View('template.phtml');
$view->title = 'Spindle Example';
$view->main  = 'Hello! Spindle/View!';

echo $view->render();
```

render()メソッドはテンプレートファイルを文字列化して返します。
render()メソッドだけでは画面に出力しないので、必要に応じてechoで出力してください。

#### 1-1. ビュー変数の渡し方

テンプレートファイルに値を渡す際は、いくつかやり方があります。

```php
//一つ一つ渡す
$view->title = 'sample';
$view->data = array(1,2,3);

//連想配列からまとめて渡す
$view->assign(array(
    'title' => 'sample',
    'data' => array(1,2,3),
));

// compactを使うとこんな書き方でもOK
$title = 'sample';
$data = array(1,2,3);
$view->assign(compact('title', 'data'));
```

一方、受け取る側のテンプレートファイルでは、変数は展開されています。
`$title`とか`$data`で参照できます。
`$this->title`や`$this->data`でも同じものが取れます。
thisを付ける書き方は冗長ですが、変数の書き換えを行った際に、挙動に違いが出ます。
詳しくはレイアウトの項で説明します。

```php
<!-- 同じ意味 -->
<title><?php echo $title ?></title>
<title><?php echo $this->title ?></title>

<ul>
<?php foreach ($data as $i): ?>
 <li><?php echo $i ?></li>
<?php endforeach ?>
</ul>
```

渡せる変数の型には制限はありません。オブジェクトをそのまま渡すこともできます。

テンプレートファイルはPHPの通常の関数と違い、どんな引数を取るのか明示していません。
あまり多くの変数を渡すより、オブジェクトにまとめて一つか二つ渡す方が
メンテナンスしやすくなると思います。

#### 1-2. ビュー変数のスコープ

ビュー変数はローカル変数です。
テンプレートファイルの中で適当に変数を作っても、その場限りであり、グローバル空間を汚染しません。


#### 1-3. テンプレートファイルの探索パス

template.phtmlをどこから探してくるかですが、特に何も指定しなければ
include\_pathを順番に探します。PHPの設定を変えていないなら、
viewのスクリプトと同じパスが最優先で探索されます。

特定のパスから指定したい場合は第2引数で探索基準パスを指定してください。
絶対パスでも相対パスでもOKです。

```php
$view = new Spindle\View('template.phtml', '/path/to/template');
```

### 2. レイアウト

テンプレートは入れ子状にすることができます。

template.phtmlが以下のように書いてあったとして、
```php
<?php $this->setLayout('layout.phtml') ?>
template
```

そしてlayout.phtmlが以下のように書いてあったとすると、
```php
<div>
<?php echo $this->content() ?>
</div>
```

template.phtmlの結果は以下のようになります。
```html
<div>
template
</div>
```

template.phtmlが親となるべきレイアウトテンプレートを指定していることに注意してください。
`$this->content()`は、子テンプレートが存在するときはその描画結果が返り、単体で呼び出される場合は空文字が返ります。

#### 2-1. テンプレートの描画順序

テンプレートは内側から描画されていきます。
そのため、ビュー変数に何か加工を加えると、外側のレイアウトテンプレートに影響を与えます。

### 3. 入れ子のレイアウト

レイアウトは無限に入れ子にできます。(実際はコンピュータのメモリの許す限り、ですが。)
親のレイアウトテンプレートが、更にsetLayoutしていれば、どんどん入れ子にできます。


License
-------------------------

Spindle/Viewの著作権は放棄するものとします。
利用に際して制限はありませんし、作者への連絡や著作権表示なども必要ありません。
スニペット的にコードをコピーして使っても問題ありません。

[ライセンスの原文](LICENSE)

CC0-1.0 (No Rights Reserved)
- https://creativecommons.org/publicdomain/zero/1.0/
- http://sciencecommons.jp/cc0/about (Japanese)

