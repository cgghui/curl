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
- $curl->add($copyName = false) 添加线程
- $curl->run($name = null) 执行curl
- $curl->runSmall($url, $method = 'get', $ssl = 1) 快速的执行curl
- $curl->get($flag = self::body, $name = null) 取出执行结果
- $curl->getAll() 取出所有执行结果
- $curl->getManager($name) 处理抓取结果的回调类


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
取出curl反馈的信息
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
$default 规定curl的一些缺省值，可以规定3个connectMaxTime（最大链接时间，单位秒），execMaxTime（等待响应时间，下载过大的文件时，该时间须要长一些，单位秒），User-Agent（浏览器报表） 
```php
$default = array(
  'connectMaxTime' => 30,
  'execMaxTime' => 60,
  'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
);

$curl = new curl($default);

```


