<?php
namespace server\lib;

class Functions {
	public static function dataToJson($data)
	{
		return is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
	}

	public static function jsonToData($json)
	{
		return json_decode($json, true);
	}

	public static function doCurlPostRequest($url, $post_fields = '', $headers = '', $timeout = 20, $file = 0)
	{
		$ch = curl_init();//初始化一个的curl对话，返回一个链接资源句柄
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => false,
			CURLOPT_NOBODY => false,
			CURLOPT_POST => true,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		);
		if (is_array($post_fields) && $file == 0) {
			$options[CURLOPT_POSTFIELDS] = http_build_query($post_fields);
		} else {
			$options[CURLOPT_POSTFIELDS] = $post_fields;
		}
		curl_setopt_array($ch, $options);
		if (is_array($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		$result = curl_exec($ch);	//执行一个的curl对话
		$code = curl_errno($ch);	//返回一个的包含当前对话错误消息的数字编号
		$msg = curl_error($ch);		//返回一个的包含当前对话错误消息的char串
		$info = curl_getinfo($ch);	//获取一个的curl连接资源的消息
		curl_close($ch);			//关闭对话，并释放资源
		return array(
			'data' => $result,
			'code' => $code,
			'msg' => $msg,
			'info' => $info
		);
	}

}