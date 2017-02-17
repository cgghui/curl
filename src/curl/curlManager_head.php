<?php

/**

$curl = new curl();

$curl->add()->opt_targetURL('https://www.baidu.com', 2)->done('get','a');
$curl->add()->opt_targetURL('http://image.baidu.com/')->done('get','b');
$curl->add()->opt_targetURL('https://zhidao.baidu.com/',2)->done('get','c');

$curl->run();

$head = $curl->getManager('head');

if ($head->used('b') === true) {
    print_r($head->output());
    print_r($head->status());
    print_r($head->contentType());
    print_r($head->cookieToArray());
    print_r($head->cookieToString());
}

*/

class curlManager_head
{

    private $head = array();
    private $iSingle = false;

    private $format = array();

	public function __construct(curl $curl)
	{
        $this->head = $curl->get(curl::head);
        $this->iSingle = !is_array($this->head);
	}

    /**
     * 解析header数据
     *
     * 单一线程时，$name必须指定为null； 多线程时，必须指定$name
     * 多线程时，如须遍历所有线程下返回的header，可用$curl->threadNames取出所有线程名称
     *
     * @param null $name 配置线程名称
     *
     * @return bool
     */
    public function used($name = null)
    {
        if ($name === null && $this->iSingle === true) {
            $head = $this->head;
        } else {
            if (isset($this->head[$name]) === true) {
                $head = $this->head[$name];
            } else {
                $head = false;
            }
        }
        if ($head === false) {
            return false;
        }

        $headers = array();
        $key = '';
        $raw = explode("\n", $head);
        foreach ($raw as $i=>$h) {
            $h = explode(':', $h, 2);
            if (isset($h[1]) === true) {
                if (isset($headers[$h[0]]) === false) {
                    $headers[$h[0]] = trim($h[1]);
                }
                elseif (is_array($headers[$h[0]]) === true) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                }
                else{
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }
                $key = $h[0];
            }else{
                if (substr($h[0], 0, 1) === "\t") {
                    $headers[$key] .= "\r\n\t" . trim($h[0]);
                } elseif(!$key) {
                    $headers[0] = trim($h[0]);
                }
            }
        }
        $this->format = $headers;

        return true;
    }

    public function output()
    {
        return $this->format;
    }

    public function has($field)
    {
        $field = explode('-', $field);
        foreach ($field as &$f) {
            $f = ucfirst(strtolower($f));
        }
        return isset($this->format[implode('-', $field)]);
    }

    public function get($field)
    {
        $field = explode('-', $field);
        foreach ($field as &$f) {
            $f = ucfirst(strtolower($f));
        }
        return $this->format[implode('-', $field)];
    }

    public function status()
    {
        return $this->has(0)===true ? explode(' ', $this->format[0], 3) : false;
    }

    public function contentType()
    {
        return $this->has('Content-Type') === true ? $this->format['Content-Type'] : false;
    }

    public function cookieToArray()
    {
        if($this->has('Set-Cookie') === false){
            return false;
        }
        date_default_timezone_set('Asia/Shanghai');

        $this->format['Set-Cookie'] = (array)$this->format['Set-Cookie'];

        $cookie = array();
        foreach ($this->format['Set-Cookie'] as $i=>$item) {
            $item = explode(';', $item);
            $name = '';
            foreach ($item as $j=>$attr) {
                $attr = explode('=', trim($attr), 2);
                $field = trim($attr[0]);
                $value = trim($attr[1]);
                if ($field === 'expires') {
                    $value = date('Y-m-d H:i:s', strtotime($value));
                }
                if ($j === 0) {
                    $name = $field;
                    $cookie[$name] = array('value' => $value);
                }else{
                    $cookie[$name][$field] = $value;
                }
            }
        }

        return $cookie;
    }

    public function cookieToString()
    {
        if( $this->has('Set-Cookie')===false ){
            return '';
        }
        $result = array();

        $cookies = $this->cookieToArray();
        foreach ($cookies as $name=>$cookie) {
            $result[$name] = $name . '=' . $cookie['value'];
        }

        return implode('; ',$result);
    }

}