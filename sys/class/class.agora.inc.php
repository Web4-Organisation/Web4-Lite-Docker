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

class agora extends db_connect
{
	private $requestFrom = 0;
    private $language = 'en';

	public function __construct($dbo = NULL)
    {
		parent::__construct($dbo);
	}

    public function newVideoCall($fromUserId, $toUserId, $channel)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $currentTime = time();
        $ip_addr = helper::ip_addr();
        $u_agent = helper::u_agent();

        $stmt = $this->db->prepare("INSERT INTO agora (fromUserId, toUserId, channel, createAt, ip_addr, u_agent) value (:fromUserId, :toUserId, :channel, :createAt, :ip_addr, :u_agent)");
        $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
        $stmt->bindParam(":toUserId", $toUserId, PDO::PARAM_INT);
        $stmt->bindParam(":channel", $channel, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
        $stmt->bindParam(":u_agent", $u_agent, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                "error" => false,
                "error_code" => ERROR_SUCCESS,
                "itemId" => $this->db->lastInsertId()
            );

            $fcm = new fcm($this->db);
            $fcm->setRequestFrom($this->getRequestFrom());
            $fcm->setRequestTo($toUserId);
            $fcm->setType(GCM_NOTIFY_AGORA_VIDEO_CALL);
            $fcm->setTitle("You have new video call");
            $fcm->setItemId($result['itemId']);
            $fcm->setAppType(APP_TYPE_ANDROID);
            $fcm->prepare();
            $fcm->send();
            unset($fcm);
        }

        return $result;
    }

    public function statusVideoCall($itemId, $status, $time)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $itemInfo = $this->info($itemId);

        if ($itemInfo['error']) {

            return $result;
        }

        if ($itemInfo['fromUserId'] != $this->getRequestFrom() && $itemInfo['toUserId'] != $this->getRequestFrom()) {

            return $result;
        }

        if ($itemInfo['callStatus'] != VIDEO_CALL_ACTIVE) {

            return $result;
        }

        $stmt = $this->db->prepare("UPDATE agora SET callStatus = (:callStatus), callTime = (:callTime) WHERE id = (:itemId)");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $stmt->bindParam(":callStatus", $status, PDO::PARAM_INT);
        $stmt->bindParam(":callTime", $time, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                "error" => false,
                "error_code" => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function info($itemId)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("SELECT * FROM agora WHERE id = (:itemId) LIMIT 1");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $time = new language($this->db, $this->language);

                $result = array(
                    "error" => false,
                    "error_code" => ERROR_SUCCESS,
                    "id" => $row['id'],
                    "fromUserId" => $row['fromUserId'],
                    "toUserId" => $row['toUserId'],
                    "channel" => $row['channel'],
                    "callStatus" => $row['callStatus'],
                    "callTime" => $row['callTime'],
                    "date" => date("Y-m-d H:i:s", $row['createAt']),
                    "timeAgo" => $time->timeAgo($row['createAt']),
                    "createAt" => $row['createAt'],
                    "removeAt" => $row['removeAt']
                );

                $profile = new profile($this->db, $row['fromUserId']);
                $profileFromUserId = $profile->getVeryShort();
                unset($profile);

                $profile = new profile($this->db, $row['toUserId']);
                $profileToUserId = $profile->getVeryShort();
                unset($profile);

                $result['fromUserPhotoUrl'] = $profileFromUserId['lowPhotoUrl'];
                $result['fromUserUsername'] = $profileFromUserId['username'];
                $result['fromUserFullname'] = $profileFromUserId['fullname'];
                $result['toUserPhotoUrl'] = $profileToUserId['lowPhotoUrl'];
                $result['toUserUsername'] = $profileToUserId['username'];
                $result['toUserFullname'] = $profileToUserId['fullname'];
            }
        }

        return $result;
    }

    public function get($itemId = 0)
    {
        if ($itemId == 0) {

            $itemId = 10000000;
            $itemId++;
        }

        $guests = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "itemId" => $itemId,
                        "items" => array());

        $stmt = $this->db->prepare("SELECT id FROM agora WHERE guestTo = (:guestTo) AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':guestTo', $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $guestInfo = $this->info($row['id']);

                array_push($guests['items'], $guestInfo);

                $guests['itemId'] = $guestInfo['id'];

                unset($guestInfo);
            }
        }

        return $guests;
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
