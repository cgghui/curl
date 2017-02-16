# Curl PHP v1.0
一个小巧方便快捷的 PHP CURL 库，可以用它进行网页内容的抓取，文件的下载，API接口的通信等操作，总之它是方便的，快捷的。除此之外，它还具备多线程能力。

### curl静态属性
- [curl::head](#curlhead)
- curl::body(#curl::body)  取出body部分的内容
- curl::info(#curl::info)  取出curl反馈的信息
- curl::file(#curl::file)  取出下载的文件信息（仅对启用了下载的线程有效）
- curl::isDown(#curl::isDown)  是否是一个下载线程
- curl::error(#curl::error)   取出curl的报错信息

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


#curl::head
取出目标站点响应的header标头
```php
$curl = new curl();

$curl->add()->opt_targetURL('http://php.net')->done();

$curl->run();

echo $curl->get(curl::head);
```
