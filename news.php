<?php
header("Content-Type: text/html; charset=UTF-8");

function _news()
{
    $fileContent = strval(file_get_contents("news.txt"));
    return array(
        1,
        $fileContent
    );
}

function _isserveropen()
{}

?>