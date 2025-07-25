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

class spam extends db_connect
{
	private $requestFrom = 0;
    private $language = 'en';

	public function __construct($dbo = NULL)
    {
		parent::__construct($dbo);
	}

	// Get created chats count for last 30 minutes

    public function getChatsCount()
    {
        $testTime = time() - 1800; // 30 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM chats WHERE fromUserId = (:userId) AND createAt > (:testTime)");
        $stmt->bindParam(":userId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get created items count for last 5 minutes

    public function getItemsCount()
    {
        $testTime = time() - 300; // 5 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE fromUserId = (:userId) AND createAt > (:testTime)");
        $stmt->bindParam(":userId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get user see profiles count for last 5 minutes

    public function getGuestsCount()
    {
        $testTime = time() - 300; // 5 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM guests WHERE guestId = (:profileId) AND removeAt = 0 AND createAt > (:testTime)");
        $stmt->bindParam(":profileId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get user send friend requests count for last 30 minutes

    public function getSendFriendRequestsCount()
    {
        $testTime = time() - 1800; // 30 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM profile_followers WHERE follower = (:profileId) AND create_at > (:testTime)");
        $stmt->bindParam(":profileId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get created market items count for last 10 minutes

    public function getMarketItemsCount()
    {
        $testTime = time() - 600; // 10 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM market_items WHERE fromUserId = (:userId) AND createAt > (:testTime)");
        $stmt->bindParam(":userId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get created market items count for last 15 minutes

    public function getGalleryItemsCount()
    {
        $testTime = time() - 900; // 15 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM gallery WHERE fromUserId = (:userId) AND createAt > (:testTime)");
        $stmt->bindParam(":userId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get user send gifts count for last 30 minutes

    public function getSendGiftsCount()
    {
        $testTime = time() - 1800; // 30 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM gifts WHERE giftFrom = (:profileId) AND createAt > (:testTime)");
        $stmt->bindParam(":profileId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get created item comments count for last 30 minutes

    public function getItemCommentsCount()
    {
        $testTime = time() - 1800; // 30 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM comments WHERE fromUserId = (:profileId) AND createAt > (:testTime)");
        $stmt->bindParam(":profileId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    // Get created gallery comments count for last 30 minutes

    public function getGalleryCommentsCount()
    {
        $testTime = time() - 1800; // 30 minutes

        $stmt = $this->db->prepare("SELECT count(*) FROM gallery_comments WHERE fromUserId = (:profileId) AND createAt > (:testTime)");
        $stmt->bindParam(":profileId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":testTime", $testTime, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
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
