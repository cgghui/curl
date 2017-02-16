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
- $curl->threadNames  取出所有线程的名称

### curl方法
- $curl->__construct($default = array())
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
## curl::file
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
