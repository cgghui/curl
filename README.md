# Curl PHP v1.0
一个小巧方便快捷的 PHP CURL 库，可以用它进行网页内容的抓取，文件的下载，API接口的通信等操作，总之它是方便的，快捷的。除此之外，它还具备多线程能力。

### curl静态属性
以下属性仅适用于$curl->get()方法的第一个参数，如果curl线程为下载文件线程，curl::head 和 curl::body 是无效的

- [curl::head](#curlhead)  取出header部分的内容
- [curl::body](#curlbody)  取出body部分的内容
- [curl::info](#curlinfo)  取出curl反馈的信息
- [curl::file](#curlfile)  取出下载的文件信息 仅对启用了下载的线程有效
- [curl::isDown](#curlisDown)  是否是一个下载线程
- [curl::error](#curlerror)   取出curl的报错信息

### curl动态属性
- [$curl->threadNames](#curl-threadnames)  取出所有线程的名称

### curl方法
- [$curl->__construct($default = array())](#curl-__constructdefault--array)
- [$curl->add($copyName = false)](#curl-addcopyname--false) 添加线程
- [$curl->run($name = null)](#curl-__constructdefault--array) 执行curl
- [$curl->runSmall($url, $method = 'get', $ssl = 1)](#curl-__constructdefault--array) 快速的执行curl
- [$curl->get($flag = self::body, $name = null)](#curl-__constructdefault--array) 取出执行结果
- [$curl->getAll()](#curl-__constructdefault--array) 取出所有执行结果
- [$curl->getManager($name)](#curl-__constructdefault--array) 处理抓取结果的回调类


## curl::head
取出目标站点响应的header标头
```php
$curl = new curl();

$curl->add()->opt_targetURL('http://php.net')->done();

$curl->run();

echo $curl->get(curl::head);
```

## curl::body
取出目标站点响应的body主体内容
```php
$curl = new curl();

$curl->add()->opt_targetURL('http://php.net')->done();

$curl->run();

echo $curl->get(curl::body);
```

## curl::info
取出curl反馈的信息
```php
$curl = new curl();

$curl->add()->opt_targetURL('http://php.net')->done();

$curl->run();

print_r($curl->get(curl::info));
```
## curl::file
取出下载的文件信息 仅对启用了下载的线程有效 开启php_fileinfo扩展可返回文件的mime类型
```php
$curl = new curl();

$curl->add()
  ->opt_targetURL('http://php.net')
  ->opt_download('php.html')
  ->done();
  
$curl->run();

print_r($curl->get(curl::file));
```
## curl::isDown
是否是一个下载线程
```php
$curl = new curl();

$curl->add()
  ->opt_targetURL('http://php.net')
  ->opt_download('php.html')
  ->done();
$curl->run();
var_dump($curl->get(curl::isDown)); // true

$curl->add()->opt_targetURL('http://php.net')->done();
$curl->run();
var_dump($curl->get(curl::isDown)); // false
```
## curl::error
取出错误信息
```php
$curl = new curl();

$curl->add()->opt_targetURL('http://php.net')->done();

$curl->run();

print_r($curl->get(curl::error));
```

## $curl->threadNames
取出所有线程的名称
```php
$curl = new curl();

$curl->add()->opt_targetURL('http://php.net')->done('get', 'a');
$curl->add()->opt_targetURL('https://www.baidu.com', 2)->done('get','b');
$curl->add()->opt_targetURL('http://image.baidu.com/')->done('get','c');
$curl->add()->opt_targetURL('https://zhidao.baidu.com/',2)->done('get','d');

/*
run之前的结果 array()
*/
print_r($curl->threadNames);

$curl->run();

/*
run之后的结果 array(
  0 => a
  1 => b
  2 => c
  3 => d
)
*/
print_r($curl->threadNames);
```

## $curl->__construct($default = array())
- $default 规定curl的一些缺省值
```php
$default = array(
  'timeOut' => array(
    'connectMaxTime' => 30,
    'execMaxTime' => 60
  ),
  'header' => array(
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
  )
);

$curl = new curl($default);

$curl->add()->opt_targetURL('http://php.net')->done();

$curl->run();

echo $curl->get(curl::body);

```

## $curl->add($copyName = false)
添加一个抓取线程

- $copyName 复制线程 复制一个已有线程，提供已有线程的名称即可

该方法返回的是配置线程对象（curlThreadOptions）,该对象可通过$curl->add()进行使用
以下为配置线程对象中的可用外部方法

- $curlThreadOptions->opt_targetURL($url, $ssl = 1) 设置目标站点的url
- $curlThreadOptions->opt_isGetBody($is) 是否取回主体内容
- $curlThreadOptions->opt_isGetHead($is) 是否取回标头内容
- $curlThreadOptions->opt_download($savePath) 下载文件
- $curlThreadOptions->opt_proxy($ip, $port = '80', $username = '', $password = '', $socks5 = false) 使用代理访问目标站点
- $curlThreadOptions->opt_jump($max = 3) 自动跳转
- $curlThreadOptions->opt_timeOut($execMaxTime = 30, $connectMaxTime = 0) 请求超时设置
- $curlThreadOptions->opt_sendHeader($name, $value) 向目标站点发送的header数据
- $curlThreadOptions->opt_sendCookie($field, $value) 向目标站点发送的cookie数据
- $curlThreadOptions->opt_sendPost($field, $value = null) 向目标站点发送的post数据
- $curlThreadOptions->opt_sendPostCustom($data, $format = null) 向目标站点发送的自定义数据
- $curlThreadOptions->done($requestMethod = 'get', $name = null) 完成配置
```php
$curl = new curl();

$curl->add()
  ->opt_targetURL('http://image5.tuku.cn/pic/dongwushijie/endearing_dog_181/098.jpg')
  ->opt_download('098.jpg')
  ->opt_timeOut(120, 10)
  ->opt_proxy('127.0.0.1:8080')
->done('get', 'a');

/*
 * 复制a线程给b线程
 * b线程中更改了opt_targetURL和opt_download配置，其它将继承a线程的所有配置信息，
 * 即b线程同a线程一样，最大链接时间为10秒，最大下载时间为120秒，且使用代理127.0.0.1:8080
 */
$curl->add('a')
  ->opt_targetURL('http://image5.tuku.cn/pic/dongwushijie/endearing_dog_181/099.jpg')
  ->opt_download('099.jpg')
->done('get', 'b');

$curl->run();

print_r($curl->get(curl::file));
```

## $curl->run($name = null)
执行线程，如未指定$name(线程名称)，即执行所有线程，执行完毕后所有线程将被释放；如指定$name，即执行$name线程，执行完毕后$name线程会被释放，而没有被执行的线程不会被释放，如何释放，可通过$curl->free()进行释放。
```php
$curl = new curl();

$cookies = array(
  'BIDUPSID' => '109CBB215F051223E78E0328F4586147',
  'PSTM' => '1486296197',
  '__cfduid' => 'd4b92399c102c843eee0176ecbbf5be8a1486296206',
  'BAIDUID' => '109CBB215F051223E78E0328F4586147:SL=0:NR=50:FG=1',
  'MCITY' => '-179%3A'
);

$thread = $curl->add()->opt_targetURL('https://www.baidu.com/', 2);
foreach ($cookies as $name=>$value) {
  $thread->opt_sendCookie($name, $value);
}
$thread->done('get', 'a');

// 复制a线程给b线程
$curl->add('a')
  ->opt_targetURL('https://www.baidu.com/s?wd=php', 2)
->done('get', 'b');

// a线程执行完毕即被释放
$curl->run('a');
echo $curl->get(curl::body);

// 复制失败 a线程已被释放 c线程不具有a线程的cookie
$curl->add('a')
  ->opt_targetURL('https://www.baidu.com/s?wd=php+curl', 2)
->done('get', 'c');

// 复制成功 b线程尚未被执行
$curl->add('b')
  ->opt_targetURL('https://www.baidu.com/s?wd=php+curl', 2)
->done('get', 'e');



```

## $curl->runSmall($url, $method = 'get', $ssl = 1) 

```php

```

## $curl->get($flag = self::body, $name = null) 
```php

```

## $curl->getAll() 
```php

```

## $curl->getManager($name) 
```php

```
