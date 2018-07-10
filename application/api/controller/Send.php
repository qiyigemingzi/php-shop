<?php
/**
 * 向客户端发送相应基类
 */
namespace app\api\controller;
use think\Config;
use think\Response;
use think\response\Redirect;
trait Send
{
    /**
     * 默认返回资源类型
     * @var string
     */
    protected $restDefaultType = 'json';
    /**
     * 设置响应类型
     * @param null $type
     * @return $this
     */
    public function setType($type = null)
    {
        $this->type = (string)(!empty($type)) ? $type : $this->restDefaultType;
        return $this;
    }

    /**
     * 失败响应
     * @param int $code
     * @param string $massage
     * @param array $data
     * @param array $headers
     * @param array $options
     * @return Response|\think\response\Json|\think\response\Jsonp|\think\response\Xml
     */
    public function formatError($code = 400, $massage = 'error', $data = [], $headers = [], $options = [])
    {
        $massages = Config::get('massage');
        $extmsg = $massages[$code];

        if (strpos($extmsg, '%s') !== false) {
            $massage = sprintf($extmsg, $massage);
        } else {
            $massage = $massage != 'error' ? $massage : $extmsg;
        }
        $responseData['code'] = (int)$code;
        $responseData['message'] = $massage;
        $responseData['data'] = $data;
        $responseData = array_merge($responseData, $options);
        return $this->response($responseData, $code, $headers);
    }
    /**
     * 成功响应
     * @param array $data
     * @param string $message
     * @param int $code
     * @param array $headers
     * @param array $options
     * @return Response|\think\response\Json|\think\response\Jsonp|Redirect|\think\response\Xml
     */
    public function formatSuccess($data = [], $message = 'success', $code = 200, $headers = [], $options = [])
    {
        $responseData['code'] = 200;
        $responseData['message'] = (string)$message;
        $responseData['data'] = $data;
        $responseData = array_merge($responseData, $options);
        return $this->response($responseData, $code, $headers);
    }
    /**
     * 重定向
     * @param $url
     * @param array $params
     * @param int $code
     * @param array $with
     * @return Redirect
     */
    public function sendRedirect($url, $params = [], $code = 302, $with = [])
    {
        $response = new Redirect($url);
        if (is_integer($params)) {
            $code = $params;
            $params = [];
        }
        $response->code($code)->params($params)->with($with);
        return $response;
    }
    /**
     * 响应
     * @param $responseData
     * @param $code
     * @param $headers
     * @return Response|\think\response\Json|\think\response\Jsonp|Redirect|\think\response\View|\think\response\Xml
     */
    public function response($responseData, $code, $headers)
    {
        if (!isset($this->type) || empty($this->type)) $this->setType();
        $headers['Access-Control-Allow-Origin']  = '*';
        $headers['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type';
        $headers['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';
        return Response::create($responseData, $this->type, $code, $headers);
    }

    /**
     * 如果需要允许跨域请求，请在记录处理跨域options请求问题，并且返回200，以便后续请求，这里需要返回几个头部。。
     * @param int $code
     * @param string $message
     * @param array $data
     * @param array $header
     */
    public function returnMsg($code = 400, $message = '',$data = [],$header = [])
    {
        http_response_code($code);    //设置返回头部
        $return['code'] = $code;
        $return['message'] = $message;
        if (!empty($data)) $return['data'] = $data;
        // 发送头部信息
        foreach ($header as $name => $val) {
            if (is_null($val)) {
                header($name);
            } else {
                header($name . ':' . $val);
            }
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }
}