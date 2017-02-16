<?php

$curl = new curl();

$curl->add()->opt_targetURL('https://www.baidu.com', 2)->done('get','a');
$curl->add()->opt_targetURL('http://image.baidu.com/')->done('get','b');
$curl->add()->opt_targetURL('https://zhidao.baidu.com/',2)->done('get','c');

$curl->run();

$head = $curl->getManager('head');
$body = $curl->getManager('body');
$body = $curl->getManager('body');
$body = $curl->getManager('body');

if ($body->used('a') === true) {

    $jquery = $body->jquery(); // https://github.com/Imangazaliev/DiDOM

    echo $jquery->find('#lh > a:nth-child(1)')[0]->attr('href');
    echo "\n";
    echo $jquery->find('#lh > a:nth-child(2)')[0]->attr('href');
    echo "\n";
    echo $jquery->find('#lh > a:nth-child(3)')[0]->attr('href');
    echo "\n";
    echo $jquery->find('#lh > a:nth-child(4)')[0]->attr('href');
    echo "\n\n";

    $links = $jquery->find('a');
    foreach ($links as $link) {
        echo $link->attr('href');
        echo "\n";
    }

}

/**
 * 远程操作库
 */
class curl
{

    /**
     *  适用于get方法的第一个参数 取出header部分的内容
     */
    const head = 'head';

    /**
     *  适用于get方法的第一个参数 取出body部分的内容
     */
    const body = 'body';

    /**
     *  适用于get方法的第一个参数 取出curl反馈的信息
     */
    const info = 'info';

    /**
     *  适用于get方法的第一个参数 取出下载的文件信息（仅对启用了下载的线程有效）
     */
    const file = 'file';

    /**
     *  适用于get方法的第一个参数 取出curl的报错信息
     */
    const error = 'error';

    /**
     *  适用于get方法的第一个参数 是否是一个下载线程
     */
    const isDown = 'isDown';

    /**
     * 记录着所有的配置线程名称
     *
     * 该属性在执行run方法后才会有数据，且必须是多线程时
     *
     * @var array
     */
    public $threadNames = array();

    /**
     * 配置curl线程的管理对象
     *
     * @var curlThreadManager|null
     */
    private $threadManager = null;

    /**
     * 执行curl的操作对象
     *
     * @var curlRun|null
     */
    private $run = null;

    /**
     * 单例实例对象 供 getManager 方法使用
     * 调用 run 方法会将该属性清空
     */
    private $callIns = array();

    /**
     * curl constructor.
     *
     * @param array $default 默认配置
     *  可设置 execMaxTime, connectMaxTime, User-Agent
     */
    public function __construct($default = array())
    {
        $this->threadManager = new curlThreadManager();
        $this->run = new curlRun($default);
    }

    /**
     * 添加curl配置线程
     *
     * @param bool $copyName 复制一个已有配置线程 提供已有线程的名称
     *
     * @return curlThreadOptions
     */
    public function add($copyName = false)
    {
        return new curlThreadOptions($this->threadManager, $copyName);
    }

    /**
     * 执行线程
     *
     * @param null $name 指定配置线程的名称 执行指定的配置线程
     * @return bool
     */
    public function run($name = null)
    {
        $this->callIns = null;
        $this->callIns = array();

        // 无配置线程
        if ($this->threadManager->isEmpty() === true) {
            return false;
        }
        $result = true;

        // 没有指定配置线程
        if ($name === null) {
            // 单线
            if ($this->threadManager->iSingle() === true) {
                $this->run->singleThread($this->threadManager->first());
            }
            // 多线
            else {
                // 配置线程名称记录到$this->threadNames
                $threads = $this->threadManager->get();
                foreach ($threads as $name=>$thread) {
                    $this->threadNames[] = $name;
                }
                // 执行
                $this->run->multiThread($threads);
            }
        }
        // 指定线程
        else {
            if ($this->threadManager->has($name) === true) {
                $this->run->singleThread($this->threadManager->get($name));
            } else {
                $result = false;
            }
        }

        // 删除所有线程
        $this->threadManager->delete();

        return $result;
    }

    /**
     * 小巧快捷的执行一个线程
     *
     * @param string $url 链接地址
     * @param string $method 提交方法
     * @param int $ssl ssl验证 非1即2 $url为https时有效
     *
     * @return bool
     */
    public function runSmall($url, $method = 'get', $ssl = 1)
    {
        $this->add()->opt_targetURL($url, $ssl)->done($method);
        return $this->run();
    }

    /**
     * 取出结果
     *
     * @param string $flag 参考本类常量
     * @param null $name 多线程可以指定名称取出指定的线程结果
     *
     * @return array|bool
     */
    public function get($flag = self::body, $name = null)
    {
        $result = $this->run->getResult();

        if (isset($result[$flag]) === false) {
            return false;
        }
        $result = $result[$flag];

        if ($name !== null) {
            $result = isset($result[$name]) === true ? $result[$name] : false;
        }

        return $result;
    }

    /**
     * 取回所有结果
     *
     * @return array
     */
    public function getAll()
    {
        return $this->run->getResult();
    }

    /**
     * 处理抓取结果的回调类
     *
     * 该方法以单例模式返回回调类对象
     *
     * $name为curl.php所在目录下的以curlManager_$name.php的类文件
     * 该类文件的类名必须命名为curlManager_$name，该类必须可被实例化
     *
     * @param string $name 类名
     *
     * @return bool|object
     */
    public function getManager($name)
    {
        if (isset($this->callIns[$name]) === true) {
            return $this->callIns[$name];
        }

        $name = 'curlManager_' . $name;
        $path = __DIR__ . DIRECTORY_SEPARATOR . $name . '.php';

        if (is_file($path) === false) {
            return false;
        }
        if (in_array($name, get_declared_classes()) === false) {
            require($path);
        }
        if (class_exists($name, false) === false) {
            return false;
        }

        $ref = new ReflectionClass($name);
        if ($ref->IsInstantiable() === false) {
            $ref = false;
        } else {
            $ref = $ref->newInstance($this);
        }
        $this->callIns[str_replace('curlManager_', '', $name)] = $ref;

        return $ref;
    }

}

/**
 * 配置线程管理类
 */
class curlThreadManager
{
    /**
     * 所有配置线程
     *
     * @var array
     */
    private $threads = array();

    /**
     * 加入一个配置线程
     *
     * $name 不指定时，为$this->threads总长度+1
     *
     * @param array $option 配置信息 由curlThreadOptions类构成
     * @param null $name 线程名
     *
     * @return bool
     */
    public function add($option, $name = null)
    {
        if ($name === null) {
            $name = count($this->threads);
        }
        $this->threads[$name] = $option;

        return true;
    }

    /**
     * 取出配置线程中的第一个线程
     *
     * @return array|mixed
     */
    public function first()
    {
        $thread = $this->threads;
        $thread = reset($thread);

        return $thread;
    }

    /**
     * 检测配置线程是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->threads);
    }

    /**
     * 检测配置线程是否存在
     *
     * @param string $name 配置线程名称
     *
     * @return bool
     */
    public function has($name)
    {
        $result = false;
        if (isset($this->threads[$name]) === true) {
            $result = true;
        }

        return $result;
    }

    /**
     * 取出配置线程
     *
     * $name 指定为null时，取出所有配置线程
     * 该方法不检测$name的有效性，使用前请用has方法进行指判
     *
     * @param null $name 配置线程名称
     *
     * @return array
     */
    public function get($name = null)
    {
        if ($name === null) {
            return $this->threads;
        }

        return $this->threads[$name];
    }

    /**
     * 是否为单线程
     *
     * @return bool
     */
    public function iSingle()
    {
        return count($this->threads) === 1 ? true : false;
    }

    /**
     * 删除一条线程
     *
     * @param null $name 配置线程名称
     *
     * @return bool
     */
    public function delete($name = null)
    {
        if ($name === null) {
            $this->threads = array();
            return true;
        }

        if ($this->has($name) === true) {
            unset($this->threads[$name]);
        }

        return true;
    }

}


/**
 * 配置线程处理类
 */
class curlThreadOptions
{
    /**
     * @var curlThreadManager|null
     */
    private $manage = null;

    /**
     * @var array
     */
    private $option = array();

    /**
     * curlThreadOptions constructor.
     *
     * @param curlThreadManager $manage
     * @param bool $copyName
     */
    public function __construct(curlThreadManager $manage, $copyName = false)
    {
        $this->manage = $manage;

        if (empty($copyName) === false) {
            $option = $this->manage->get($copyName);
            if ($option !== false) {
                $this->option = $option;
            }
        }
    }

    /**
     * 设置目标站点
     *
     * 如果$url没有scheme和host，会抛出异常
     *
     * @param string $url 目标站点链接
     * @param int $ssl ssl 类型 $url非https时，该参数无效
     *
     * @return $this
     * @throws Exception
     */
    public function opt_targetURL($url, $ssl = 1)
    {
        $info = parse_url($url);
        if (isset($info['scheme'], $info['host']) === false) {
            throw new Exception('invalid url "' . $url . '"');
        }

        if (isset($info['path']) === true) {
            if (isset($info['query']) === true && empty($info['query']) === false) {
                $info['fullPath'] = $info['path'] . '/?' . $info['query'];
            } else {
                $info['fullPath'] = '/';
            }
        } else {
            $info['fullPath'] = '/';
        }

        $this->option['url'] = array(
            'url' => $url,
            'ssl' => $info['scheme'] === 'https' ? true : false,
            'sslt' => in_array($ssl, array(1, 2)) === true ? $ssl : 1,
            'host' => $info['host'],
            'path' => $info['fullPath']
        );

        return $this;
    }

    /**
     * 是否取回body数据
     *
     * @param boolean $is true 取回 false 不取回
     *
     * @return $this
     */
    public function opt_isGetBody($is)
    {
        $this->option['viewNobody'] = !$is;

        return $this;
    }

    /**
     * 是否取回header数据
     *
     * @param boolean $is true 取回 false 不取回
     *
     * @return $this
     */
    public function opt_isGetHead($is)
    {
        $this->option['viewHeader'] = $is;

        return $this;
    }

    /**
     * 下载文件
     *
     * @param string $savePath 文件保存路径
     *
     * @return $this
     */
    public function opt_download($savePath)
    {
        $this->option['download'] = array(fopen($savePath, 'wb'), $savePath);

        return $this;
    }

    /**
     * 使用代理
     *
     * @param string $ip 代理ip地址
     * @param string $port 代理ip端口
     * @param string $username 代理ip帐号
     * @param string $password 代理ip密码
     * @param bool $socks5 代理类型是否为socks5
     *
     * @return $this
     */
    public function opt_proxy($ip, $port = '80', $username = '', $password = '', $socks5 = false)
    {

        if (strpos($ip, ':') !== false && $port === '80') {
            $ip = explode(':', $ip, 2);
            $port = trim($ip[1]);
            $ip = trim($ip[0]);
        }

        $this->option['proxy'] = array(
            'ip'        =>  $ip,
            'port'      =>  $port,
            'username'  =>  $username,
            'password'  =>  $password,
            'socks5'    =>  $socks5
        );

        return $this;
    }

    /**
     * 自动跳转
     *
     * @param int $max 自动跳转的次数
     *
     * @return $this
     */
    public function opt_jump($max = 3)
    {
        $this->option['jump'] = intval($max);

        return $this;
    }

    /**
     * 请求超时
     *
     * @param int $execMaxTime  最大执行时间 单位 秒
     * @param int $connectMaxTime 最大连接时间 单位 秒
     *
     * @return $this
     */
    public function opt_timeOut($execMaxTime = 30, $connectMaxTime = 0)
    {
        $this->option['timeOut'] = array(
            'execMaxTime'       =>  intval($execMaxTime),
            'connectMaxTime'    =>  intval($connectMaxTime)
        );

        return $this;
    }

    /**
     * 发送请求header
     *
     * @param string $name 名称
     * @param string $value 值
     *
     * @return $this
     */
    public function opt_sendHeader($name, $value)
    {
        if (empty($name) === false)
        {
            $this->option['header'][ucfirst(strtolower($name))] = $value;
        }

        return $this;
    }

    /**
     * 发送请求Cookie
     *
     * @param string $field 名称
     * @param string $value 值
     *
     * @return $this
     */
    public function opt_sendCookie($field, $value)
    {
        $this->option['cookie'][$field] = $value;

        return $this;
    }

    /**
     * 发送请求Post数据
     *
     * 标准POST请求，以http_build_query函数生成的数据为准
     *
     * @param string $field 名称
     * @param string $value 值
     *
     * @return $this
     */
    public function opt_sendPost($field, $value = null)
    {
        $this->option['post'][$field] = $value;

        return $this;
    }

    /**
     * 发送请求 自定义Post数据
     *
     * 非标准POST请求 根据目标站的数据格式为准
     *
     * @param string|array $data 数据
     * @param string $format 自助格式 使用自且格式$data必须是键值对应的数组
     *
     * @return $this
     */
    public function opt_sendPostCustom($data, $format = null)
    {
        if (is_array($data) === true) {
            switch ($format) {
                case 'json':
                    $this->option['post'] = json_encode($data);
                break;
                case 'serialize':
                    $this->option['post'] = serialize($data);
                break;
                case 'httpQuery':
                    $this->option['post'] = http_build_query($data);
                break;
            }
        }

        return $this;
    }

    /**
     * 完成配置
     *
     * 每条线程配置完成后必须调用该方法
     * 返回配置数据
     *
     * @param string $requestMethod 请求类型 如：get, post, put 等
     * @param null $name 线程名称
     *
     * @return array
     */
    public function done($requestMethod = 'get', $name = null)
    {
        $this->option['requestMethod'] = $requestMethod;

        $this->manage->add($this->option, $name);
        return $this->option;
    }

}


/**
 * 执行配置线程类
 */
class curlRun
{
    /**
     * 执行结果
     *
     * @var array
     */
    private $result = array();

    /**
     * 默认设定
     *
     * @var array
     */
    private $threadOptionDefault = array();

    /**
     * curlRun constructor.
     *
     * @param array $threadOptionDefault 由 curl类实例化时提供
     */
    public function __construct($threadOptionDefault)
    {
        $this->threadOptionDefault = $threadOptionDefault;
    }

    /**
     * 跑单线程
     *
     * @param array $threadOptions 配置线程
     *
     * @return null
     */
    public function singleThread($threadOptions)
    {
        $result = array(
            curl::head => '',
            curl::body => '',
            curl::file => array(),
            curl::info => array(),
            curl::error => '',
            curl::isDown => false
        );

        $structure = new curlStructure($threadOptions, $this->threadOptionDefault);

        $ch = $structure->getCurlHandle();
        $response = curl_exec($ch);
        $result[curl::info] = curl_getinfo($ch);
        $result[curl::error] = curl_error($ch);
        curl_close($ch);

        $download = $this->checkDownload($threadOptions, $result[curl::info]);
        if ($download === false) {
            if ($structure->getThreadOption('viewHeader') === true) {
                $response = $this->splitHeaderAndBody($response);
                $result[curl::head] = $response['header'];
                $result[curl::body] = $response['body'];
            }else{
                $result[curl::head] = '';
                $result[curl::body] = $response;
            }
        }else{
            $result[curl::isDown] = true;
            $result[curl::file] = $download;
        }

        $this->result = $result;

        return null;
    }

    /**
     * 跑多线程
     *
     * @param array $threadOptionAll 所有配置线程
     *
     * @return null
     */
    public function multiThread($threadOptionAll)
    {
        $multi = curl_multi_init();

        $curls = array();
        $heads = array();
        foreach ($threadOptionAll as $name=>$threadOptions) {
            $structure = new curlStructure($threadOptions, $this->threadOptionDefault);
            $curls[$name] = $structure->getCurlHandle();
            $heads[$name] = $structure->getThreadOption('viewHeader');
            curl_multi_add_handle($multi, $curls[$name]);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($multi,$active);
        } while($mrc === CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc === CURLM_OK) {
            while (curl_multi_exec($multi, $active) === CURLM_CALL_MULTI_PERFORM);
            if (curl_multi_select($multi) !== -1) {
                do {
                    $mrc = curl_multi_exec($multi,$active);
                } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            }
        }

        $result = array(
            curl::head => array(),
            curl::body => array(),
            curl::file => array(),
            curl::info => array(),
            curl::error => array(),
            curl::isDown => array()
        );

        foreach($curls as $name=>$ch){

            $response = curl_multi_getcontent($ch);

            $result[curl::head][$name] = '';
            $result[curl::body][$name] = '';
            $result[curl::isDown][$name] = false;
            $result[curl::file][$name] = array();
            $result[curl::info][$name] = curl_getinfo($ch);
            $result[curl::error][$name] = curl_error($ch);

            curl_multi_remove_handle($multi,$ch);
            curl_close($ch);

            $download = $this->checkDownload($threadOptionAll[$name], $result[curl::info][$name]);
            if ($download === false) {
                if ($heads[$name] === true) {
                    $response = $this->splitHeaderAndBody($response);
                    $result[curl::head][$name] = $response['header'];
                    $result[curl::body][$name] = $response['body'];
                }else{
                    $result[curl::head][$name] = '';
                    $result[curl::body][$name] = $response;
                }
            }else{
                $result[curl::isDown][$name] = true;
                $result[curl::file][$name] = $download;
            }

        }

        curl_multi_close($multi);

        $this->result = $result;

        return null;
    }

    /**
     * 取出结果
     *
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * 检测是否是下载线程
     *
     * 非下载线程返回false
     *
     * @param array $threadOptions 配置线程
     *
     * @return array|bool
     */
    private function checkDownload($threadOptions, $info)
    {
        if (isset($threadOptions['download']) === false) {
            return false;
        }
        fclose($threadOptions['download'][0]);

        $mime = '';
        if (extension_loaded('Fileinfo') === true) {
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $threadOptions['download'][1]);
        }

        $return = array(
            'path' => $threadOptions['download'][1],
            'size' => $info['download_content_length'],
            'actualSize' => filesize($threadOptions['download'][1]),
            'mime' => $mime,
            'code' => $info['http_code']
        );
        return $return;
    }

    /**
     * 切分body和header
     *
     * @param string $document 取回的内容
     *
     * @return array
     */
    private function splitHeaderAndBody($document)
    {
        if (empty($document) === true) {
            return array('header' => '', 'body' => '');
        }

        $document = explode("\r\n\r\n", $document, 2);
        if (isset($document[1]) === false) {
            $document[1] = $document[0];
            $document[0] = '';
        }

        $firstLine = explode("\n", $document[0])[0];
        $firstLine = explode(' ', $firstLine, 3);
        if (trim($firstLine[1])==='200' && trim($firstLine[2]) !== 'OK') {
            $document = explode("\r\n\r\n", $document[1], 2);
            if (isset($document[1]) === false) {
                $document[1] = $document[0];
                $document[0] = '';
            }
        }

        return array('header' => $document[0], 'body' => $document[1]);
    }
}

/**
 * 构造curl类
 */
class curlStructure
{

    /**
     * 请求类型
     *
     * @var string
     */
    private $requestMethod = '';

    /**
     * 配置线程
     *
     * @var array
     */
    private $threadOptions = array();

    /**
     * 配置线程默认设置
     *
     * @var array
     */
    private $threadOptionDefault = array(
        'execMaxTime' => 15,
        'connectMaxTime' => 15,
        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36'
    );

    /**
     * curl句柄
     *
     * @var null|resource
     */
    private $curlHandle = NULL;

    /**
     * curlStructure constructor.
     *
     * @param array $threadOptions
     * @param array $threadOptionDefault
     */
    public function __construct($threadOptions, $threadOptionDefault)
    {
        $this->requestMethod = strtoupper($threadOptions['requestMethod']);
        $this->threadOptions = $threadOptions;
        $this->threadOptionDefault = array_merge($this->threadOptionDefault, $threadOptionDefault);
        $this->curlHandle = curl_init();
    }

    /**
     * 取出构造后的配置线程
     *
     * 提供$item参数，则返回配置线程中的相关设置，否则返回全部
     *
     * @param null $item 配置线程的键名
     *
     * @return array
     */
    public function getThreadOption($item = null)
    {
        if ($item === null) {
            return $this->threadOptions;
        }

        return $this->threadOptions[$item];
    }

    /**
     * 取出curl句柄
     *
     * @return null|resource
     */
    public function getCurlHandle()
    {
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, 1);
        $this
            ->opt_targetURL()
            ->opt_proxy()
            ->opt_jump()
            ->opt_timeOut()
            ->opt_sendHeader()
            ->opt_sendData()
            ->opt_download()
            ->opt_view();

        return $this->curlHandle;
    }

    private function opt_targetURL()
    {
        $option = $this->threadOptions['url'];

        curl_setopt($this->curlHandle, CURLOPT_URL, $option['url']);
        if ($option['ssl'] === true) {
            curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYHOST, $option['sslt']);
        }

        return $this;
    }

    private function opt_proxy()
    {
        if (isset($this->threadOptions['proxy']) === false) {
            return $this;
        }
        $option = $this->threadOptions['proxy'];

        curl_setopt($this->curlHandle, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($this->curlHandle, CURLOPT_PROXY, $option['ip']);
        curl_setopt($this->curlHandle, CURLOPT_PROXYPORT, $option['port']);
        if ($option['username'] !== '') {
            curl_setopt($this->curlHandle, CURLOPT_PROXYUSERPWD, $option['username'] . ':' . $option['password']);
        }
        curl_setopt($this->curlHandle, CURLOPT_PROXYTYPE, $option['socks5'] === true ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP);

        return $this;
    }

    private function opt_jump()
    {
        if (isset($this->threadOptions['jump']) === false || $this->threadOptions['jump'] <= 0) {
            return $this;
        }

        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curlHandle, CURLOPT_MAXREDIRS, $this->threadOptions['jump']);

        return $this;
    }

    private function opt_timeOut()
    {
        if (isset($this->threadOptions['timeOut']) === false) {
            $this->threadOptions['timeOut'] = array(
                'execMaxTime' => $this->threadOptionDefault['execMaxTime'],
                'connectMaxTime' => $this->threadOptionDefault['connectMaxTime']
            );
        }
        $option = $this->threadOptions['timeOut'];

        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $option['execMaxTime']);
        curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT, $option['connectMaxTime']);

        return $this;
    }

    private function opt_sendHeader()
    {
        $headers = array();
        $headers['Host'] = $this->threadOptions['url']['host'];
        $headers['Connection'] = 'keep-alive';
        $headers['Cache-Control'] = 'max-age=0';
        $headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $headers['Upgrade-Insecure-Requests'] = '1';
        $headers['User-Agent'] = $this->threadOptionDefault['User-Agent'];
        if (isset($this->threadOptions['header']) === true) {
            $this->threadOptions['header'] = array_merge($headers, $this->threadOptions['header']);
        } else {
            $this->threadOptions['header'] = $headers;
        }

        $cookie = '';
        if (isset($this->threadOptions['cookie']) === true) {
            foreach ($this->threadOptions['cookie'] as $field=>$value) {
                if ($cookie !== '') {
                    $cookie .= '; ';
                }
                $cookie .= $field . '=' . urlencode($value);
            }
        }
        if ($cookie!=='') {
            $this->threadOptions['header']['Cookie'] = $cookie;
        }

        $headers = array();
        $headers[] = $this->requestMethod . ' ' . $this->threadOptions['url']['path'].' HTTP/1.1';
        foreach ($this->threadOptions['header'] as $name=>$value) {
            $headers[] = $name . ': ' . $value;
        }

        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headers);

        return $this;
    }

    private function opt_sendData()
    {
        switch ($this->requestMethod) {
            case 'GET':
                curl_setopt($this->curlHandle, CURLOPT_HTTPGET, true);
            break;
            case 'POST':
                curl_setopt($this->curlHandle, CURLOPT_POST, true);
            break;
            default:
                curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, $this->requestMethod);
            break;
        }

        if($this->requestMethod !== 'GET' && isset($this->threadOptions['post']) === true) {
            $post = $this->threadOptions['post'];
            $post = is_array($post)===true ? http_build_query($post) : $post;
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $post);
        }

        return $this;
    }

    private function opt_download(){
        if (isset($this->threadOptions['download']) === true) {
            curl_setopt($this->curlHandle, CURLOPT_FILE, $this->threadOptions['download'][0]);
        }

        return $this;
    }

    private function opt_view()
    {
        if (isset($this->threadOptions['download']) === true) {
            $this->threadOptions['viewHeader'] = false;
        } else {
            $this->threadOptions['viewHeader'] = isset($this->threadOptions['viewHeader']) === true ? $this->threadOptions['viewHeader'] : true;
        }
        $this->threadOptions['viewNobody'] = isset($this->threadOptions['viewNobody'])===true ? $this->threadOptions['viewNobody'] : false;

        curl_setopt($this->curlHandle, CURLOPT_HEADER, $this->threadOptions['viewHeader']);
        curl_setopt($this->curlHandle, CURLOPT_NOBODY, $this->threadOptions['viewNobody']);

        return $this;
    }

}