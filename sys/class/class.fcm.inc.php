<?php

/*!
 * Linkspreed UG
 * Web4 Lite published under the Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License. (BY-NC-SA 4.0)
 *
 * https://linkspreed.com
 * https://web4.one
 *
 * Copyright (c) 2025 Linkspreed UG (hello@linkspreed.com)
 * Copyright (c) 2025 Marc Herdina (marc.herdina@linkspreed.com)
 * 
 * Web4 Lite (c) 2025 by Linkspreed UG & Marc Herdina is licensed under Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-nc-sa/4.0/.
 */

if (!defined("APP_SIGNATURE")) {

    header("Location: /");
    exit;
}

require_once '../sys/addons/vendor/autoload.php';

class fcm extends db_connect
{
    private $requestFrom = 0; // Sender Account ID
    private $language = 'en';

    private $requestTo = 0; // Identifier of the recipient account

    private $type = 0; // Notification type
    private $title = 0; // Notification title
    private $itemId = 0; // Notifications for: object or item identifier

    private $appType = -1; // Notifications for apps types

    private $message = array();

    private $access_token = "";
    private $url = "https://fcm.googleapis.com/v1/projects/".FIREBASE_PROJECT_ID."/messages:send";
    private $ids = array();
    private $data = array();

    public function __construct($dbo = NULL)
    {
        parent::__construct($dbo);

        $this->setAccessToken($this->getToken());
    }

    private function getToken()
    {
        $jsonFileName = "";

        if ($files = glob("js/firebase/*.json")) {

            $jsonFileName = $files[0];
        }

        $client = new Google_Client();

        try {

            $client->setAuthConfig("$jsonFileName");
            $client->addScope(Google_Service_FirebaseCloudMessaging::CLOUD_PLATFORM);

            $savedTokenJson = $this->readSavedToken();

            if ($savedTokenJson) {

                // the token exists, set it to the client and check if it's still valid

                $client->setAccessToken($savedTokenJson);
                $accessToken = $savedTokenJson;

                if ($client->isAccessTokenExpired()) {

                    // the token is expired, generate a new token and set it to the client
                    $accessToken = $this->generateToken($client);
                    $client->setAccessToken($accessToken);
                }

            } else {

                // the token doesn't exist, generate a new token and set it to the client
                $accessToken = $this->generateToken($client);
                $client->setAccessToken($accessToken);
            }


            $oauthToken = $accessToken["access_token"];

            return $oauthToken;

        } catch (Google_Exception $e) {


        }

        return false;
    }

    //Using a simple file to cache and read the toke, can store it in a databse also
    private function readSavedToken()
    {

        $tk = @file_get_contents(DB_NAME.'.firebase.token');
        if ($tk) return json_decode($tk, true); else return false;
    }

    private function writeToken($tk)
    {

        file_put_contents(DB_NAME.'.firebase.token', $tk);
    }

    private function generateToken($client)
    {
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken();

        $tokenJson = json_encode($accessToken);
        $this->writeToken($tokenJson);

        return $accessToken;
    }

    public function send()
    {
        $result = array(
            "error" => true,
            "description" => "regId not found"
        );

        if (empty($this->ids)) {

            return $result;
        }

        //

        $apiurl = 'https://fcm.googleapis.com/v1/projects/'.FIREBASE_PROJECT_ID.'/messages:send';   //replace "your-project-id" with...your project ID

        $headers = [
            'Authorization: Bearer '.$this->access_token,
            'Content-Type: application/json'
        ];

        $notification_tray = [
            'title'             => "Some title",
            'body'              => "Some content",
        ];

        $in_app_module = [
            "title"          => "Some data title (optional)",
            "body"           => "Some data body (optional)",
        ];

        $message = [
            'message' => [
                //'notification'     => $notification_tray,
                //'token'             => $this->ids[0],
                'data'             => $this->data,
            ],
        ];

        foreach ($this->ids as $token) {

            $message['message']['token'] = $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiurl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

            $result = curl_exec($ch);

            if ($result === FALSE) {
                //Failed
                die('Curl failed: ' . curl_error($ch));
            }

            curl_close($ch);
        }

        //

//        $boundary = "--subrequest_boundary";
//        $multiPayload = $boundary;
//
//        foreach ($this->ids as $token) {
//
//            $head = "Content-Type: application/http\r\n".
//                "Content-Transfer-Encoding: binary\r\n\r\n".
//                "POST /v1/projects/".FIREBASE_PROJECT_ID."/messages:send\r\n".
//                "Content-Type: application/json\r\n".
//                "accept: application/json\r\n\r\n";
//
//            $payload = ["message" => ["token" => $token, "data" => $this->data]];
//
//            $postdata = json_encode($payload);
//            $multiPayload .= "\r\n".$head.$postdata."\r\n".$boundary;
//
//        }
//
//        $multiPayload .= "--";
//
//        $opts = array('http' =>
//            array(
//                'method'  => 'POST',
//                'header'  => 'Content-Type: multipart/mixed; boundary="subrequest_boundary"' . "\r\nAuthorization: Bearer $this->access_token",
//                'content' => $multiPayload
//            )
//        );
//
//        $context  = stream_context_create($opts);
//
//        //file_put_contents("result2.php", $context);
//
//
//        // This is the path for sending push multiple tokens (upto 500 as per the docs)
//        $result = file_get_contents('https://fcm.googleapis.com/batch', false, $context);

//        file_put_contents("result.php", $result);

        $obj = json_encode($result, true);

        return $obj;
    }

    public function prepare()
    {

        if ($this->getAppType() == APP_TYPE_ALL) {

            $stmt = $this->db->prepare("SELECT fcm_regId FROM access_data WHERE accountId = (:accountId) AND removeAt = 0 AND appType > 1 AND fcm_regId <> ''"); // appType = 1 -> APP_TYPE_WEB
            $stmt->bindParam(":accountId", $this->requestTo, PDO::PARAM_INT);

        } else {

            $stmt = $this->db->prepare("SELECT fcm_regId FROM access_data WHERE accountId = (:accountId) AND removeAt = 0 AND appType = (:appType) AND fcm_regId <> ''");
            $stmt->bindParam(":accountId", $this->requestTo, PDO::PARAM_INT);
            $stmt->bindParam(":appType", $this->appType, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $this->ids[] = $row['fcm_regId'];
            }
        }

        $this->data = array(
            "type" => "{$this->getType()}",
            "msg" => $this->getTitle(),
            "id" => "{$this->getItemId()}",
            "accountId" => "{$this->getRequestTo()}"
        );

        if (count($this->getMessage()) != 0) {

            $this->data['msgId'] = "{$this->message['id']}";
            $this->data['msgFromUserId'] = "{$this->message['fromUserId']}";
            $this->data['msgFromUserState'] = "{$this->message['fromUserState']}";
            $this->data['msgFromUserVerify'] = "{$this->message['fromUserVerify']}";
            $this->data['msgFromUserOnline'] = "{$this->message['fromUserOnline']}";
            $this->data['msgFromUserUsername'] = $this->message['fromUserUsername'];
            $this->data['msgFromUserFullname'] = $this->message['fromUserFullname'];
            $this->data['msgFromUserPhotoUrl'] = $this->message['fromUserPhotoUrl'];
            $this->data['msgMessage'] = "{$this->message['message']}";
            $this->data['msgImgUrl'] = $this->message['imgUrl'];
            $this->data['stickerId'] = "{$this->message['stickerId']}";
            $this->data['stickerImgUrl'] = $this->message['stickerImgUrl'];
            $this->data['msgCreateAt'] = "{$this->message['createAt']}";
            $this->data['msgDate'] = $this->message['date'];
            $this->data['msgTimeAgo'] = "{$this->message['timeAgo']}";
            $this->data['msgRemoveAt'] = "{$this->message['removeAt']}";
        }
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    public function getItemId()
    {
        return $this->itemId;
    }

    public function setAppType($appType)
    {
        $this->appType = $appType;
    }

    public function getAppType()
    {
        return $this->appType;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function setRequestTo($requestTo)
    {
        $this->requestTo = $requestTo;
    }

    public function getRequestTo()
    {
        return $this->requestTo;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setRequestFrom($requestFrom)
    {
        $this->requestFrom = $requestFrom;
    }

    public function getRequestFrom()
    {
        return $this->requestFrom;
    }
}