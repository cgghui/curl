# Curl PHP v1.0
一个小巧方便快捷的 PHP CURL 库，可以用它进行网页内容的抓取，API接口的通信等操作，总之它是方便的，快捷的。

### curl静态属性（以下属性仅适用于$curl->get()方法的第一个参数）
- curl::head  取出header部分的内容
- curl::body  取出body部分的内容
- curl::info  取出curl反馈的信息
- curl::file  取出下载的文件信息（仅对启用了下载的线程有效）
- curl::isDown  是否是一个下载线程
- curl::error   取出curl的报错信息


