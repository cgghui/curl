# Curl PHP v1.0
一个小巧方便快捷的 PHP CURL 库，可以用它进行网页内容的抓取，文件的下载，API接口的通信等操作，总之它是方便的，快捷的。除此之外，它还具备多线程能力。

### curl静态属性
以下属性仅适用于$curl->get()方法的第一个参数，如果curl线程为下载文件线程，curl::head 和 curl::body 是无效的
- curl::head  取出header部分的内容
- curl::body  取出body部分的内容
- curl::info  取出curl反馈的信息
- curl::file  取出下载的文件信息（仅对启用了下载的线程有效）
- curl::isDown  是否是一个下载线程
- curl::error   取出curl的报错信息

### curl动态属性
- $curl->threadNames  取出所有线程的名称

