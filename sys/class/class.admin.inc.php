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

class admin extends db_connect
{

	private $requestFrom = 0;
    private $id = 0;

	public function __construct($dbo = NULL)
    {
		parent::__construct($dbo);
	}

    public function getCount()
    {
        $number_of_rows = 0;

        try {

            $result = $this->db->query("SELECT 1 FROM admins");

            if ($result) {

                $stmt = $this->db->prepare("SELECT 1 FROM admins");
                $stmt->execute();

                $number_of_rows = $stmt->fetchColumn();
            }

        } catch (Exception $e) {

            return $number_of_rows;
        }

        return $number_of_rows;
    }

    public function signup($username, $password, $fullname)
    {

        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        if (!helper::isCorrectLogin($username)) {

            $result = array("error" => true,
                            "error_code" => ERROR_UNKNOWN,
                            "error_type" => 0,
                            "error_description" => "Incorrect login");

            return $result;
        }

        if (!helper::isCorrectPassword($password)) {

            $result = array("error" => true,
                            "error_code" => ERROR_UNKNOWN,
                            "error_type" => 1,
                            "error_description" => "Incorrect password");

            return $result;
        }

        $salt = helper::generateSalt(3);
        $passw_hash = md5(md5($password).$salt);
        $currentTime = time();

        $stmt = $this->db->prepare("INSERT INTO admins (username, salt, password, fullname, createAt) value (:username, :salt, :password, :fullname, :createAt)");
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":salt", $salt, PDO::PARAM_STR);
        $stmt->bindParam(":password", $passw_hash, PDO::PARAM_STR);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $this->setId($this->db->lastInsertId());

            $result = array("error" => false,
                            'accountId' => $this->id,
                            'username' => $username,
                            'password' => $password,
                            'error_code' => ERROR_SUCCESS,
                            'error_description' => 'SignUp Success!');

            return $result;
        }

        return $result;
    }

    public function signin($username, $password)
    {
        $result = array('error' => true,
                        "error_code" => ERROR_UNKNOWN);

        $username = helper::clearText($username);
        $password = helper::clearText($password);

        $stmt = $this->db->prepare("SELECT salt FROM admins WHERE username = (:username) LIMIT 1");
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $row = $stmt->fetch();
            $passw_hash = md5(md5($password).$row['salt']);

            $stmt2 = $this->db->prepare("SELECT id FROM admins WHERE username = (:username) AND password = (:password) LIMIT 1");
            $stmt2->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt2->bindParam(":password", $passw_hash, PDO::PARAM_STR);
            $stmt2->execute();

            if ($stmt2->rowCount() > 0) {

                $row2 = $stmt2->fetch();

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "accountId" => $row2['id']);
            }
        }

        return $result;
    }

    public function setPassword($password, $newPassword)
    {
        $result = array('error' => true,
                        'error_code' => ERROR_UNKNOWN);

        if (!helper::isCorrectPassword($password)) {

            return $result;
        }

        if (!helper::isCorrectPassword($newPassword)) {

            return $result;
        }

        $stmt = $this->db->prepare("SELECT salt FROM admins WHERE id = (:adminId) LIMIT 1");
        $stmt->bindParam(":adminId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $row = $stmt->fetch();
            $passw_hash = md5(md5($password).$row['salt']);

            $stmt2 = $this->db->prepare("SELECT id FROM admins WHERE id = (:adminId) AND password = (:password) LIMIT 1");
            $stmt2->bindParam(":adminId", $this->id, PDO::PARAM_INT);
            $stmt2->bindParam(":password", $passw_hash, PDO::PARAM_STR);
            $stmt2->execute();

            if ($stmt2->rowCount() > 0) {

                $this->newPassword($newPassword);

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS);
            }
        }

        return $result;
    }

    public function newPassword($password)
    {
        $newSalt = helper::generateSalt(3);
        $newHash = md5(md5($password).$newSalt);

        $stmt = $this->db->prepare("UPDATE admins SET password = (:newHash), salt = (:newSalt) WHERE id = (:adminId)");
        $stmt->bindParam(":adminId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":newHash", $newHash, PDO::PARAM_STR);
        $stmt->bindParam(":newSalt", $newSalt, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function setAdmobValueForAccounts($admob)
    {
        $admob_create_at = 0;

        if ($admob != 0) {

            $admob_create_at = time();
        }

        $stmt = $this->db->prepare("UPDATE users SET admob = (:admob), admob_create_at = (:admob_create_at)");
        $stmt->bindParam(":admob", $admob, PDO::PARAM_INT);
        $stmt->bindParam(":admob_create_at", $admob_create_at, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function setId($accountId)
    {
        $this->id = $accountId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRequestFrom($requestFrom)
    {
        $this->requestFrom = $requestFrom;
    }

    public function getRequestFrom()
    {
        return $this->requestFrom;
    }

    static function isSession()
    {
        if (isset($_SESSION) && isset($_SESSION['admin_id'])) {

            return true;

        } else {

            return false;
        }
    }

    static function getCurrentAdminId()
    {
        if (admin::isSession()) {

            return $_SESSION['admin_id'];

        } else {

            return 0;
        }
    }

    static function setSession($admin_id, $access_token)
    {
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['admin_access_token'] = $access_token;
    }

    static function unsetSession()
    {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_access_token']);
    }

    static function getAccessToken()
    {
        if (isset($_SESSION) && isset($_SESSION['admin_access_token'])) {

            return $_SESSION['admin_access_token'];

        } else {

            return "undefined";
        }
    }

    static function createAccessToken()
    {
        $access_token = md5(uniqid(rand(), true));

        if (isset($_SESSION)) {

            $_SESSION['admin_access_token'] = $access_token;
        }
    }
}

