<?php

/*

$curl = new curl();

$curl->add()->opt_targetURL('https://www.baidu.com', 2)->done('get','a');
$curl->add()->opt_targetURL('http://image.baidu.com/')->done('get','b');
$curl->add()->opt_targetURL('https://zhidao.baidu.com/',2)->done('get','c');

$curl->run();

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

*/

class curlManager_body
{

    private $bodys = array();
    private $iSingle = false;

    private $body = '';

	public function __construct(curl $curl)
	{
        $this->bodys = $curl->get(curl::body);
        $this->iSingle = !is_array($this->bodys);
	}

    /**
     * 解析body数据
     *
     * 单一线程时，$name必须指定为null； 多线程时，必须指定$name
     * 多线程时，如须遍历所有线程下返回的body，可用$curl->threadNames取出所有线程名称
     *
     * @param null $name 配置线程名称
     *
     * @return bool
     */
    public function used($name = null)
    {
        $result = true;

        if ($name === null && $this->iSingle === true) {
            $this->body = $this->bodys;
        } else {
            if (isset($this->bodys[$name]) === true) {
                $this->body = $this->bodys[$name];
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * jquery形式解析body
     *
     * @link https://github.com/Imangazaliev/DiDOM
     *
     * @return bool|\DiDom\Document
     */
    public function jquery()
    {
        $didomPath = __DIR__ . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR;
        spl_autoload_register(function($className) use($didomPath) {
            $didomPath .= str_replace('/', DIRECTORY_SEPARATOR, $className) . '.php';
            include($didomPath);
        }, true, false);

        return new \DiDom\Document($this->body);
    }

    /**
     * 将body当作json解析为php数组
     *
     * @return array
     */
    public function json()
    {
        $json = json_decode($this->body, true);
        $json = empty($json) === true ? false : $json;

        return $json;
    }

    /**
     * 将body当作jsonp解析为php数组
     *
     * @param string $callback 回调数组名称
     *
     * @return array|bool
     */
    public function jsonp($callback)
    {
        $json = array();
        $json = preg_match('/' . $callback . '\((\{.+\})\)/Ui', $this->body, $json) === 0 ? false : $json[1];
        $json = empty($json) === true ? false : json_decode($json, true);
        $json = empty($json) === true ? false : $json;

        return $json;
    }

}