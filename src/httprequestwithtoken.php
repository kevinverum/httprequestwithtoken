<?php

namespace kevinverum\httprequestwithtoken;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HTTPRequestWithToken implements IHTTPRequestWithToken
{
    private $guzzle_client;

    /**
     * HTTPRequestWithToken constructor.
     */
    public function __construct($env_file_path=__DIR__ . "/..")
    {
        $dotenv = Dotenv::createImmutable($env_file_path);
        $dotenv->load();
        $this->guzzle_client = new Client(['headers' => ['Authorization' => "Bearer " . getenv("TOKEN")]]);
    }

    public function get(string $url, string $token, $onInvalidToken=null, $onError=null, string $env_file_path=__DIR__ . "/..", int $try_count=0){

        try{

            // For headers @see http://docs.guzzlephp.org/en/stable/request-options.html#headers.
            // headers can be passed as the third parameter
            return $this->guzzle_client->request('GET', $url, [ 'Authorization' => "Bearer " . $token ]);

        }catch(RequestException $re){

            if (401===$re->getCode()) {

                if (!is_null($onInvalidToken) && is_callable($onInvalidToken)) {
                    $onInvalidToken($re);
                } else {

                    if ($try_count === 0) {
                        // Generate a new token and save it to .env
                        $dotenv = Dotenv::createImmutable($env_file_path);
                        $env_vars_names = array_keys($dotenv->load());
                        $token = $this->fetchToken();

                        $env_content = array_reduce(
                            $env_vars_names,
                            function ($carry, $name) use ($token) {
                                return $carry . $name . "=" . ($name === "TOKEN" ? $token : getenv($name)) . "\n";
                            },
                            ""
                        );
                        file_put_contents($env_file_path . "/.env", $env_content);

                        // Reload token
                        putenv("TOKEN=" . $token);
                        $this->guzzle_client = new Client(['headers' => ['Authorization' => "Bearer " . getenv("TOKEN")]]);
                        return $this->get($url, $token, $onInvalidToken, $onError, $env_file_path,  1);

                    } else {
                        var_dump($re->getMessage());
                    }

                }

                return 401;

            } else {
                if (!is_null($onError) && is_callable($onError)) {
                    $onError($re);
                } else {
                    echo $re->getMessage() . "\n";
                    echo $re->getCode() . "\n";
                }
                return false;
            }
        }
    }

    public function post(string $url, array $params, array $headers) {
        // @see http://docs.guzzlephp.org/en/stable/quickstart.html (promises)
        try{
            // For headers @see http://docs.guzzlephp.org/en/stable/request-options.html#headers.
            // headers can be passed as the third parameter
            $response = json_decode($this->guzzle_client->post($url, ["form_params"=>$params], $headers)->getBody()->getContents());
            return $response->access_token;
        }catch(RequestException $re){
            echo $re->getMessage() . "\n";
            echo $re->getCode() . "\n";
            return false;
        }
    }

    public function fetchToken():string
    {
        $fetch_token_response = $this->post(
            getenv("TOKEN_API_ENDPOINT"),
            [
                "grant_type"=>getenv("HTTP_REQUEST_TOKEN_GRANT_TYPE"),
                "client_id"=>getenv("HTTP_REQUEST_TOKEN_CLIENT_ID"),
                "client_secret"=>getenv("HTTP_REQUEST_TOKEN_CLIENT_SECRET"),
                "scope"=>getenv("HTTP_REQUEST_TOKEN_SCOPE"),
            ],
            ['Content-Type'=>'application/x-www-form-urlencoded']
        );
        return $fetch_token_response;
    }


}