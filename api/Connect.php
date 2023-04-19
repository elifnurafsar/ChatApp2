<?php

$myPDO = null;

try{
    $myPDO = new PDO('sqlite:./Database/database.db', "", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $myPDO->query("PRAGMA encoding=UTF8;");
}
catch(PDOException $ex){
    return $response->withJson(
        array(
            "error" => array(
                "text"  => $ex->getMessage(),
                "code"  => $ex->getCode()
            )
        )
    );
    die;
}