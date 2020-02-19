<?php

namespace kevinverum\httprequestwithtoken;

interface IHTTPRequestWithToken
{
    public function get(string $url, string $token, $onInvalidToken, $onError, string $env_file_path, int $try_count);
    public function fetchToken():string;
}