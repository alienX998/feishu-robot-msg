<?php

namespace Feishu;

class sendMsg{

    static $webhook_url = '';//您自定义机器人webhook地址
    static $root_secret = '';//开启签名验证字符串

    protected static function setWebHookUrl($webhook_url)
    {
        self::$webhook_url = $webhook_url;
    }

    protected function setRootSecret($root_secret)
    {
        self::$root_secret = $root_secret;
    }
    /**
     * Notes: 发送飞书消息
     * User: yuncopy.chen
     * Date: 2021/2/25 下午5:37
     * function: doSendMessage
     * @param string $title
     * @param string $content
     * @param string $developer
     * @return mixed
     * @static
     */
    public static function noticeMsg($title,$content,$developer){
        try{
            if ($title && $content && $developer) {
                return self::sendRequest($title,$content,$developer);
            }
        }catch (\Exception $e){
            return false;
        }catch (\Throwable $t) {
            return false;
        }
    }


    /**
     * 发送飞书消息
     * @param $title
     * @param $content
     * @param $add_content
     * @return false|mixed
     */
    public static function noticeMsgNew($title,$content,$add_content){
        try{
            if ($title && $content) {
                return self::sendRequestNew($title,$content,$add_content);
            }
        }catch (\Exception $e){
            return false;
        } catch (\Throwable $t) {
            return false;
        }
    }


    /**
     * Notes: 执行发送
     * User: yuncopy.chen
     * Date: 2021/2/25 下午6:25
     * function: sendRequest
     * @param $title
     * @param $content
     * @param $developer
     * @return mixed
     * @static
     */
    protected static function sendRequest($title,$content,$developer){

        $timestamp = time();
        $message = "{$content}，开发者：{$developer}。";
        $data = json_encode([
            'timestamp'=>$timestamp,
            'sign'=>self::makeSign($timestamp),
            'msg_type'=>'post',
            'content'=>['post'=>['zh_cn'=>['title'=>"通知：{$title}",'content'=>[[['tag'=>'text','text'=>$message]]]]] ]
        ], JSON_UNESCAPED_UNICODE);
        $header = ['Content-Type: application/json; charset=utf-8'];
        return  self::doPostRequest(self::$webhook_url,$data,100,$header);
    }


    /**
     * Notes: 执行发送
     * function: sendRequest
     * @param $title
     * @param $content
     * @param $add_content
     * @return mixed
     * @static
     */
    protected static function sendRequestNew($title,$content,$add_content){

        $timestamp = time();
        $sed = [];
        foreach ($content as $item){
            array_push($sed,[
                ['tag'=>'text','text'=>$item]
            ]);
        }
        if($add_content){
            array_push($sed,$add_content);
        }
        $data = json_encode([
            'timestamp'=>$timestamp,
            'sign'=>self::makeSign($timestamp),
            'msg_type'=>'post',
            'content'=>[
                'post'=>
                    ['zh_cn'=>
                         [
                             'title'=>"通知：{$title}告警信息",
                            'content'=> $sed
                         ]
                    ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        $header = ['Content-Type: application/json; charset=utf-8'];
        return  self::doPostRequest(self::$webhook_url,$data,100,$header);
    }


    /**
     * Notes: HmacSHA256 算法计算签名
     * User: yuncopy.chen
     * Date: 2021/2/25 下午5:46
     * function: makeSign
     * @param string $time
     * @return string
     * @static
     */
    protected static function makeSign($time=''){
        $timestamp = $time ? $time : time();
        $secret = self::$root_secret;
        $string = "{$timestamp}\n{$secret}";
        return base64_encode(hash_hmac('sha256',"", $string,true));
    }

    /**
     * 发送请求
     * @param string $url 请求CURL
     * @param string $data 请求数据
     * @param int $timeout 请求超时时间
     * @param array $header 请求头
     * @return mixed
     */
    private static function doPostRequest($url, $data, $timeout = 10, $header = []){

        $curlObj = curl_init();
        $ssl = stripos($url,'https://') === 0 ? true : false;
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
            CURLOPT_TIMEOUT => $timeout, //设置cURL允许执行的最长秒数
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
            CURLOPT_HTTPHEADER => ['Expect:'],
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
        ];
        if (!empty($header)) {
            $options[CURLOPT_HTTPHEADER] = $header;
        }

        if ($ssl) {
            //support https
            $options[CURLOPT_SSL_VERIFYHOST] = false;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }
        curl_setopt_array($curlObj, $options);
        $returnData = curl_exec($curlObj);
        if (curl_errno($curlObj)) {
            //error message
            $returnData = curl_error($curlObj);
        }
        curl_close($curlObj);
        return $returnData;
    }
}

