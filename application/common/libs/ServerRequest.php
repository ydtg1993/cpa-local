<?php


namespace app\common\libs;


use app\model\example\admin\AgentResponseModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use think\facade\Log;

class ServerRequest
{

    public static $_POST = "POST";

    public static $_GET = "GET";

    private $accessToken;

    private $secretToken;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $responseContent;

    public function __construct() {
        $this->loadConfig();
    }

    /**
     * @param $method
     * @param $uri
     * @param $body
     * @param array $head
     * @return AgentResponseModel | bool
     * @throws GuzzleException
     */
    public function request($method, $uri, $body, $head = []) {
        $head['app'] = config("database.app_id");
        $requestData['form_params'] = $this->requestBodyHandle($body);
        $requestData['headers'] = $head;
        $responseObject = $this->client->request($method, $uri, $requestData);
        $resultContent = $responseObject->getBody()->getContents();
        $this->responseContent = $resultContent;
        if($result = json_decode($resultContent, true)) {
            if(!isset($result['state']) || $result['state']!=0){
                Log::error("拉取安装数据失败：".json_encode($result,300));
                return false;
            }
            $agentResponse = new AgentResponseModel();
            $agentResponse->setState($result['state']);
            $agentResponse->setMessage($result['message']);
            $agentResponse->setData($result['data']);
            return $agentResponse;
        }else{
            return false;
        }
    }

    /**
     * 加载配置
     */
    private function loadConfig() {
        $this->accessToken = config("app.qqc_server_access_token");
        $this->secretToken = config("app.qqc_server_secret_token");
        $this->client = new Client(['base_uri' => config("database.qqc_server_url")??config("app.qqc_server_url")]);
    }

    /**
     * @param $body
     * @return array
     * 生成签名并且组装到请求参数中
     */
    private function requestBodyHandle($body) {
        if(!is_array($body)) {
            return [];
        }
        $body['timestamp'] = time();
        $body['access_token'] = $this->accessToken;
        $body['secret_token'] = $this->secretToken;
        ksort($body);
        $signStr = "";
        $loop = 0;
        foreach ($body as $key => $value) {
            if($loop == 0) {
                $signStr .= $key . "=" . $value;
            }else{
                $signStr .= "&" . $key . "=" . $value;
            }
            $loop++;
        }
        $body['sign'] = strtoupper(md5($signStr));
        unset($body['secret_token']);
        return $body;
    }

    /**
     * @return string
     */
    public function getResponseContent()
    {
        return $this->responseContent;
    }
}