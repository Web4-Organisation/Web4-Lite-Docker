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

class media extends db_connect
{
    private $requestFrom = 0;
    private $language = 'en';

    public function __construct($dbo = NULL)
    {
        parent::__construct($dbo);
    }

    public function getFavorites($itemId = 0)
    {
        if ($itemId == 0) {

            $itemId = 10000000;
        }

        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS,
            "itemId" => $itemId,
            "items" => array()
        );

        $stmt = $this->db->prepare("SELECT id, itemId FROM likes WHERE removeAt = 0 AND id < (:itemId) AND fromUserId = (:fromUserId) ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':fromUserId', $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $post = new post($this->db);
                    $post->setLanguage($this->getLanguage());
                    $post->setRequestFrom($this->requestFrom);
                    $postInfo = $post->info($row['itemId']);
                    unset($post);

                    array_push($result['items'], $postInfo);

                    $result['itemId'] = $row['id'];

                    unset($postInfo);
                }
            }
        }

        return $result;
    }

    public function get($itemId = 0, $language = 'en')
    {
        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS,
            "itemId" => $itemId,
            "alerts" => array(),
            "items" => array()
        );

        if ($itemId == 0) {

            $itemId = 10000000;

            $result['itemId'] = $itemId;
        }

        $stmt = $this->db->prepare("SELECT * FROM posts WHERE videoUrl <> '' AND accessMode = 0 AND postType = 0 AND groupId = 0 AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $post = new post($this->db);
                    $post->setLanguage($this->getLanguage());
                    $post->setRequestFrom($this->getRequestFrom());
                    $postInfo = $post->quikInfo($row);
                    unset($post);

                    array_push($result['items'], $postInfo);

                    $result['itemId'] = $postInfo['id'];

                    unset($postInfo);
                }
            }
        }

        return $result;
    }

    public function getImages($itemId = 0, $language = 'en')
    {
        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS,
            "itemId" => $itemId,
            "alerts" => array(),
            "items" => array()
        );

        if ($itemId == 0) {

            $itemId = 10000000;

            $result['itemId'] = $itemId;
        }

        $stmt = $this->db->prepare("SELECT * FROM posts WHERE videoUrl = '' AND imgUrl <> '' AND accessMode = 0 AND postType = 0 AND groupId = 0 AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $post = new post($this->db);
                    $post->setLanguage($this->getLanguage());
                    $post->setRequestFrom($this->getRequestFrom());
                    $postInfo = $post->quikInfo($row);
                    unset($post);

                    array_push($result['items'], $postInfo);

                    $result['itemId'] = $postInfo['id'];

                    unset($postInfo);
                }
            }
        }

        return $result;
    }

    public function getRecentlyDeletedCount()
    {
        $count = 0;

        $time = time() - (14 * 24 * 3600); // 14 days

        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE fromUserId = (:fromUserId) AND removeAt > (:removeAt) AND allowRestore = 1");
        $stmt->bindParam(':fromUserId', $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(':removeAt', $time, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $count = $stmt->fetchColumn();
        }

        return $count;
    }

    public function getRecentlyDeleted($itemId = 0)
    {
        if ($itemId == 0) {

            $itemId = 1000000;
            $itemId++;
        }

        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS,
            "itemId" => $itemId,
            "items" => array()
        );

        $time = time() - (14 * 24 * 3600); // 14 days

        $stmt = $this->db->prepare("SELECT * FROM posts WHERE fromUserId = (:fromUserId) AND removeAt > (:removeAt) AND allowRestore = 1 AND id < (:itemId) ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':fromUserId', $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->bindParam(':removeAt', $time, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $post = new post($this->db);
                    $post->setLanguage($this->getLanguage());
                    $post->setRequestFrom($this->getRequestFrom());
                    $postInfo = $post->quikInfo($row);
                    unset($post);

                    array_push($result['items'], $postInfo);

                    $result['itemId'] = $postInfo['id'];

                    unset($postInfo);
                }
            }
        }

        return $result;
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

