<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

function fetch($url, $params = array(), $method = "get")
{
    $query = '';
    if ($method == "get") {
        $query = '?' . http_build_query($params);
    }
    $url = "https://api.betaseries.com" . $url . $query;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if ($method == "post") {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }

    /*
    if ($method != "get" || $method != "post") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    */

    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $data = substr($response, $headerSize);
    curl_close($ch);

    return json_decode($data);
}

$app->post("/api/betaseries/watched/{id}", function (Request $request, $id) use ($app) {

    $auth = fetch("/members/auth", array("key" => $app["bs.apikey"], "login" => $app["bs.login"], "password" => md5($app["bs.passwd"])), "post");
    if (empty($auth->errors)) {
        $watched = fetch("/episodes/watched", array("key" => $app["bs.apikey"], "token" => $auth->token, "id" => $id, "bulk" => true), "post");
    }

    return (isset($watched)) ? $app->json($watched) : $app->json($auth);
});

// not used yet
$app->delete("/api/betaseries/watched/{name}", function (Request $request, $name) use ($app) {

    $auth = fetch("/members/auth", array("key" => $app["bs.apikey"], "login" => $app["bs.login"], "password" => md5($app["bs.passwd"])), "post");
    if (empty($auth->errors)) {
        $watched = fetch("/episodes/watched", array("key" => $app["bs.apikey"], "token" => $auth->token, "id" => $id), "delete");
    }

    return (isset($watched)) ? $app->json($watched) : $app->json($auth);
});

$app->get("/api/betaseries/info/{name}", function (Request $request, $name) use ($app) {

    $params = array("key" => $app["bs.apikey"]);

    if ($app["bs.login"] && $app["bs.passwd"]) {
        $auth = fetch("/members/auth", array("key" => $app["bs.apikey"], "login" => $app["bs.login"], "password" => md5($app["bs.passwd"])), "post");
        if (empty($auth->errors)) {
            $params = array_merge($params, array("token" => $auth->token));
        }
    }

    $data = array();
    if ($app["bs.apikey"]) {
        $data = fetch("/episodes/scraper", array_merge($params, array("file" => $name)));
        if (!empty($data->errors))
            $data = fetch("/movies/scraper", array_merge($params, array("file" => $name)));
    }

    return $app->json($data);
});

