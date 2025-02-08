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

class phone extends db_connect
{
	private $requestFrom = 0;
    private $language = 'en';

	public function __construct($dbo = NULL)
    {
		parent::__construct($dbo);
	}

    public function c_remove($itemId)
    {
        $result = array(

            "error" => false,
            "error_code" => ERROR_SUCCESS
        );

        $removeAt = time();

        $sql = "UPDATE countries_list SET remove_at = (:remove_at) WHERE id = (:itemId)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $stmt->bindParam(":remove_at", $removeAt, PDO::PARAM_INT);
        $stmt->execute();

        return $result;
    }

    public function c_add($p_code, $c_code, $c_name)
    {
        $result = array(

            "error" => false,
            "error_code" => ERROR_SUCCESS
        );

        $createAt = time();

        $sql = "INSERT INTO countries_list (p_code, c_code, c_name, create_at) value (:p_code, :c_code, :c_name, :create_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":p_code", $p_code, PDO::PARAM_INT);
        $stmt->bindParam(":c_code", $c_code, PDO::PARAM_STR);
        $stmt->bindParam(":c_name", $c_name, PDO::PARAM_STR);
        $stmt->bindParam(":create_at", $createAt, PDO::PARAM_INT);
        $stmt->execute();

        return $result;
    }

    public function c_getList($itemId, $limit = 200)
    {

        if ($itemId == 0) {

            $itemId = 1000000;
        }

        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS,
            "itemId" => $itemId,
            "items" => array()
        );

        $stmt = $this->db->prepare("SELECT * FROM countries_list WHERE id < (:itemId) AND remove_at = 0 ORDER BY id DESC LIMIT :limit");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    array_push($result['items'], array(
                        "error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "id" => $row['id'],
                        "p_code" => $row['p_code'],
                        "c_code" => htmlspecialchars_decode(stripslashes($row['c_code'])),
                        "c_name" => htmlspecialchars_decode(stripslashes($row['c_name'])),
                        "create_at" => $row['create_at']
                    ));

                    $result['itemId'] = $row['id'];
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
