<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require "Connect.php";

$app = new \Slim\App;

//Refresh periodically
header("Refresh: 10;");

//find palindrome funct that i cannot submit bc of the platform fault occurred and lost all my code.
function FindPalindrome($m, $num) {
    $res = array();
    if($num == 0)
        return $res;
    if($num <= 9){
        $num = 11;
    }
    if(strrev($num."") == $num){
        array_push($res, $num);
        $m --;
        $num ++;
    }

    while($m > 0){
        if(strrev($num."") == $num){
            array_push($res, $num);
            $m --;
        }
        $num ++;
    }
    return $res;
    
};

//login funct
function login($username, $password) {
    global $myPDO;
    if($username && $password){
        try{
            $stmt = $myPDO->prepare("SELECT password FROM Users WHERE username = ?");
            $stmt->execute([$username]); 
            $result = $stmt->fetch();
            
            if($result["password"] == $password){
                return true;
            }
        }
        catch(PDOException $ex){
            return false;
        }
    }
    else{
        return false;
    }
};

//get all messages
$app->get('/', function (Request $request, Response $response) {
    global $myPDO;
    $username = $_GET["username"];
    $username = substr($username, 1, -1);
    $password = $_GET["password"];
    $password = substr($password, 1, -1);
    try{
        $logged_in = login($username, $password);
        
        if($logged_in){
            $stmt = $myPDO->prepare("SELECT sendername, message, sentdate FROM Messages WHERE receivername = ? ORDER BY sentdate Desc;");
            $stmt->execute([$username]); 
            $result = $stmt->fetchAll();

            $res = array();
            foreach($result as $row)
            {
                $row = array_filter($row, function($k) {
                    return $k != "0" && ($k == 'sendername' || $k == 'message' ||  $k == 'sentdate');
                }, ARRAY_FILTER_USE_KEY);
                array_push($res, $row);
            }
            

            if(sizeof($res) >= 1){
                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", 'application/json')
                    ->withJson($res);
            }
            else{
                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", 'application/json')
                    ->withJson(array(
                        "error" => array(
                            "text"  => $username.", you don't have any messages!"
                        )
                    ));
            }
            header("refresh:20");
        }
        else{
            return $response
                ->withStatus(500)
                ->withHeader("Content-Type", 'application/json')
                ->withJson(array(
                    "error" => array(
                        "text"  => "Wrong credentials provided for".$username."!"
                    )
                ));
        }
    }
    catch(PDOException $ex){
        echo $ex->getMessage()." Error!";
        //die;
    }
    
});

//get messages from user x
$app->get('/from', function (Request $request, Response $response) {
    global $myPDO;
    $username = $_GET["username"];
    $username = substr($username, 1, -1);
    $sendername = $_GET["sendername"];
    $sendername = substr($sendername, 1, -1);
    $password = $_GET["password"];
    $password = substr($password, 1, -1);
    try{
        $logged_in = login($username, $password);
        
        if($logged_in){
            $stmt = $myPDO->prepare("SELECT sendername, message, sentdate FROM Messages WHERE receivername = ?1 and sendername = ?2 ORDER BY sentdate Desc;");
            $stmt->execute([$username, $sendername]); 
            $result = $stmt->fetchAll();

            $res = array();
            foreach($result as $row)
            {
                $row = array_filter($row, function($k) {
                    return $k != "0" && ($k == 'sendername' || $k == 'message' ||  $k == 'sentdate');
                }, ARRAY_FILTER_USE_KEY);
                array_push($res, $row);
            }

            if(sizeof($res) > 0){
                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", 'application/json')
                    ->withJson($res);
            }
            else{
                return $response
                    ->withStatus(200)
                    ->withHeader("Content-Type", 'application/json')
                    ->withJson(array(
                        "error" => array(
                            "text"  => $username.", you don't have any messages!"
                        )
                    ));
            }
            
        }
        else{
            return $response
                ->withStatus(500)
                ->withHeader("Content-Type", 'application/json')
                ->withJson(array(
                    "error" => array(
                        "text"  => "Wrong credentials provided for".$username."!"
                    )
                ));
        }
    }
    catch(PDOException $ex){
        echo $ex->getMessage()." Error!";
        //die;
    }
    
});

//Send bulk message: do not forget to send receivers parameter as an array
//You might re-command the sendMessage funct below to send only one receiver by using simple username in request object. 
$app->post('/sendMessage', function (Request $request, Response $response) {
    global $myPDO;
    $sender = $request->getParam("sendername");
    $password = $request->getParam("password");
    $receivers = $request->getParam("receivers");
    $message = $request->getParam("message");
    if($sender && $password && $receivers && $message){

        $logged_in = login($sender, $password);
        
        if($logged_in){
            try{
                $res_arr = array();
                foreach($receivers as  $receiver){
                    $stmt = $myPDO->prepare("INSERT INTO Messages (sendername, receivername, message, sentdate) VALUES (?1, ?2, ?3, datetime('now'))");
                    $result = $stmt->execute([$sender, $receiver, $message]); 
                    array_push($res_arr, $receiver);
                }
                
                if($result === true){
                    return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", 'application/json')
                        ->withJson(
                            array($message, $res_arr)
                        );
                }
                else{
                    return $response
                        ->withStatus(500)
                        ->withHeader("Content-Type", 'application/json')
                        ->withJson(array(
                            "error" => array(
                                "text"  => "An error occurred while sending this message!"
                            )
                        ));
                }
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
            }
        }
        else{
            return $response
            ->withStatus(500)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(array(
                "error" => array(
                    "text"  => "Wrong credentials provided for".$username."!"
                )
            ));
        }
        
    }
    else{
        return $response
            ->withStatus(500)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(array(
                "error" => array(
                    "text"  => "Sender name, receiver name and message required!"
                )
            ));
    }
});

/*
//send message
$app->post('/sendMessage', function (Request $request, Response $response) {
    global $myPDO;
    $sender = $request->getParam("sendername");
    $password = $request->getParam("password");
    $receiver = $request->getParam("receivername");
    $message = $request->getParam("message");
    if($sender && $password && $receiver && $message){

        $logged_in = login($sender, $password);
        
        if($logged_in){
            try{
                $stmt = $myPDO->prepare("INSERT INTO Messages (sendername, receivername, message, sentdate) VALUES (?1, ?2, ?3, datetime('now'))");
                $result = $stmt->execute([$sender, $receiver, $message]); 
                
                if($result === true){
                    return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", 'application/json')
                        ->withJson(array(
                            "message" => "Message: \"".$message."\" was sent to ".$receiver." ."
                        ));
                }
                else{
                    return $response
                        ->withStatus(500)
                        ->withHeader("Content-Type", 'application/json')
                        ->withJson(array(
                            "error" => array(
                                "text"  => "An error occurred while sending this message!"
                            )
                        ));
                }
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
            }
        }
        else{
            return $response
            ->withStatus(500)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(array(
                "error" => array(
                    "text"  => "Wrong credentials provided for".$username."!"
                )
            ));
        }
        
    }
    else{
        return $response
            ->withStatus(500)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(array(
                "error" => array(
                    "text"  => "Sender name, receiver name and message required!"
                )
            ));
    }
});
*/

//send palindrom message
$app->post('/sendPMessage', function (Request $request, Response $response) {
    global $myPDO;
    $sender = $request->getParam("sendername");
    $password = $request->getParam("password");
    $receiver = $request->getParam("receivername");
    if($sender && $password && $receiver){

        $logged_in = login($sender, $password);
        $e = FindPalindrome(20, 9);
        $message = implode(" ", $e);
        if($logged_in){
            try{
                $stmt = $myPDO->prepare("INSERT INTO Messages (sendername, receivername, message, sentdate) VALUES (?1, ?2, ?3, datetime('now'))");
                $result = $stmt->execute([$sender, $receiver, $message]); 
                
                if($result === true){
                    return $response
                        ->withStatus(200)
                        ->withHeader("Content-Type", 'application/json')
                        ->withJson(array(
                            "message" => "Message: \"".$message."\" was sent to ".$receiver." ."
                        ));
                }
                else{
                    return $response
                        ->withStatus(500)
                        ->withHeader("Content-Type", 'application/json')
                        ->withJson(array(
                            "error" => array(
                                "text"  => "An error occurred while sending this message!"
                            )
                        ));
                }
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
            }
        }
        else{
            return $response
            ->withStatus(500)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(array(
                "error" => array(
                    "text"  => "Wrong credentials provided for".$username."!"
                )
            ));
        }
        
    }
    else{
        return $response
            ->withStatus(500)
            ->withHeader("Content-Type", 'application/json')
            ->withJson(array(
                "error" => array(
                    "text"  => "Sender name, receiver name and message required!"
                )
            ));
    }
});

//Welcoming :-)
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});
$app->run();