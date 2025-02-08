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

class alert extends db_connect
{
    private $requestFrom = 0;
    private $language = 'en';

    public function __construct($dbo = NULL)
    {
        parent::__construct($dbo);
    }

    public function get()
    {
        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS,
            "items" => array()
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        $arr = array();

        $arr = $config['admin_account_id'];
        $admin_account_id = $arr['intValue'];

        $arr = $config['admin_account_allow_alerts'];
        $allow_alerts = $arr['intValue'];

        if ($admin_account_id == 0 || $allow_alerts == 0) {

            return $result;
        }

        $stmt = $this->db->prepare("SELECT id FROM posts WHERE fromUserId = (:fromUserId) AND postType = 0 AND removeAt = 0 ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(':fromUserId', $admin_account_id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $post = new post($this->db);
                    $post->setLanguage($this->getLanguage());
                    $post->setRequestFrom($this->getRequestFrom());
                    $postInfo = $post->info($row['id']);
                    unset($post);

                    array_push($result['items'], $postInfo);

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

