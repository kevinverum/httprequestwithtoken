<?php

require("vendor/autoload.php");

require("src/ihttprequestwithtoken.php");
require("src/httprequestwithtoken.php");

$tf = new \kevinverum\httprequestwithtoken\HTTPRequestWithToken();

$uri = "https://example.com";

$re = $tf->get($uri, "abc");
if (is_object($re)) {
    var_dump($re->getStatusCode());
    var_dump((String)$re->getBody());
} else {
    var_dump($re);
}





