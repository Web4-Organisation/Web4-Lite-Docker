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

class account extends db_connect
{

    private $id = 0;
    private $allow_multi_accounts = true;

    public function __construct($dbo, $accountId = 0)
    {

        parent::__construct($dbo);

        $this->setId($accountId);
    }

    public function signup($username, $fullname, $password, $email, $language = 'en')
    {

        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $helper = new helper($this->db);

        if (!helper::isCorrectLogin($username)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_UNKNOWN,
                "error_type" => 0,
                "error_description" => "Incorrect login"
            );

            return $result;
        }

        if ($helper->isLoginExists($username)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_LOGIN_TAKEN,
                "error_type" => 0,
                "error_description" => "Login already taken"
            );

            return $result;
        }

        if (empty($fullname)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_UNKNOWN,
                "error_type" => 3,
                "error_description" => "Empty user full name"
            );

            return $result;
        }

        if (!helper::isCorrectPassword($password)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_UNKNOWN,
                "error_type" => 1,
                "error_description" => "Incorrect password"
            );

            return $result;
        }

        if (!helper::isCorrectEmail($email)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_UNKNOWN,
                "error_type" => 2,
                "error_description" => "Wrong email"
            );

            return $result;
        }

        if ($helper->isEmailExists($email)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_EMAIL_TAKEN,
                "error_type" => 2,
                "error_description" => "User with this email is already registered"
            );

            return $result;
        }

        $salt = helper::generateSalt(3);
        $passw_hash = md5(md5($password).$salt);
        $currentTime = time();

        $ip_addr = helper::ip_addr();

        $settings = new settings($this->db);
        $app_settings = $settings->get();
        unset($settings);

        if ($app_settings['allowMultiAccountsFunction']['intValue'] != 1) {

            if ($this->checkMultiAccountsByIp($ip_addr)) {

                $result = array(
                    "error" => true,
                    "error_code" => 500,
                    "error_type" => 4,
                    "error_description" => "User with this ip is already registered"
                );

                return $result;
            }
        }

        $accountState = ACCOUNT_STATE_ENABLED;
        $default_allow_messages = $app_settings['defaultAllowMessages']['intValue'];

        $stmt = $this->db->prepare("INSERT INTO users (state, login, fullname, passw, email, salt, regtime, last_authorize, language, allowMessages, ip_addr) value (:state, :username, :fullname, :password, :email, :salt, :createAt, :last_authorize, :language, :allowMessages, :ip_addr)");
        $stmt->bindParam(":state", $accountState, PDO::PARAM_INT);
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->bindParam(":password", $passw_hash, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":salt", $salt, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":last_authorize", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":language", $language, PDO::PARAM_STR);
        $stmt->bindParam(":allowMessages", $default_allow_messages, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $this->setId($this->db->lastInsertId());

            if (BONUS_SIGNUP != 0) {

                $this->setBalance(BONUS_SIGNUP);

                $payments = new payments($this->db);
                $payments->setRequestFrom($this->getId());
                $payments->create(PA_BUY_REGISTRATION_BONUS, PT_BONUS, BONUS_SIGNUP);
                unset($payments);
            }

            $result = array(
                "error" => false,
                'accountId' => $this->id,
                'username' => $username,
                'password' => $password,
                'error_code' => ERROR_SUCCESS,
                'error_description' => 'SignUp Success!'
            );

            return $result;
        }

        return $result;
    }

    public function signupOauth($oauth_type, $oauth_id, $fullname, $email, $language = 'en')
    {

        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $helper = new helper($this->db);

        $username = 'uid'.$helper::generateId(6);

        if ($helper->isLoginExists($username)) {

            $username = 'uid'.$helper::generateId(7);
        }

        if (strlen($fullname) == 0) {

            $fullname = "User ".$username;
        }

        if (!helper::isCorrectEmail($email) || $helper->isEmailExists($email)) {

            $email = "";
        }

        $currentTime = time();

        $ip_addr = helper::ip_addr();

        $settings = new settings($this->db);
        $app_settings = $settings->get();
        unset($settings);

        if ($app_settings['allowMultiAccountsFunction']['intValue'] != 1) {

            if ($this->checkMultiAccountsByIp($ip_addr)) {

                $result = array(
                    "error" => true,
                    "error_code" => 500,
                    "error_type" => 4,
                    "error_description" => "User with this ip is already registered"
                );

                return $result;
            }
        }

        $account_free = 1; // The account was created using OAUTH authorization and depends on it

        if ($oauth_type == OAUTH_TYPE_PHONE) {

            $account_free = 2; // account created using phone number
        }

        $accountState = ACCOUNT_STATE_ENABLED;
        $default_allow_messages = $app_settings['defaultAllowMessages']['intValue'];

        $stmt = $this->db->prepare("INSERT INTO users (account_free, state, login, fullname, email, regtime, last_authorize, language, allowMessages, ip_addr) value (:account_free, :state, :username, :fullname, :email, :createAt, :last_authorize, :language, :allowMessages, :ip_addr)");
        $stmt->bindParam(":account_free", $account_free, PDO::PARAM_INT);
        $stmt->bindParam(":state", $accountState, PDO::PARAM_INT);
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":last_authorize", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":language", $language, PDO::PARAM_STR);
        $stmt->bindParam(":allowMessages", $default_allow_messages, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $this->setId($this->db->lastInsertId());

            if (BONUS_SIGNUP != 0) {

                $this->setBalance(BONUS_SIGNUP);

                $payments = new payments($this->db);
                $payments->setRequestFrom($this->getId());
                $payments->create(PA_BUY_REGISTRATION_BONUS, PT_BONUS, BONUS_SIGNUP);
                unset($payments);
            }

            switch ($oauth_type) {

                case OAUTH_TYPE_GOOGLE: {

                    $this->setGoogleFirebaseId($oauth_id);

                    break;
                }

                case OAUTH_TYPE_APPLE: {

                    $this->setAppleId($oauth_id);

                    break;
                }

                case OAUTH_TYPE_PHONE: {

                    break;
                }

                default: {

                    $this->setFacebookId($oauth_id);

                    break;
                }
            }

            $result = array(
                "error" => false,
                'accountId' => $this->id,
                'username' => $username,
                'error_code' => ERROR_SUCCESS,
                'error_description' => 'SignUp Success!'
            );

            return $result;
        }

        return $result;
    }

    public function signin($username, $password)
    {
        $access_data = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $username = helper::clearText($username);
        $password = helper::clearText($password);

        $stmt = $this->db->prepare("SELECT salt FROM users WHERE login = (:username) LIMIT 1");
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $row = $stmt->fetch();
            $passw_hash = md5(md5($password).$row['salt']);

            $stmt2 = $this->db->prepare("SELECT id, state FROM users WHERE login = (:username) AND passw = (:password) LIMIT 1");
            $stmt2->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt2->bindParam(":password", $passw_hash, PDO::PARAM_STR);
            $stmt2->execute();

            if ($stmt2->rowCount() > 0) {

                $row = $stmt2->fetch();

                $access_data = array(
                    "error" => false,
                    "error_code" => ERROR_SUCCESS,
                    "accountId" => $row['id'],
                    "state" => $row['state']
                );
            }
        }

        return $access_data;
    }

    public function logout($accountId, $accessToken)
    {
        $auth = new auth($this->db);
        $auth->remove($accountId, $accessToken);
    }

    public function checkMultiAccountsByIp($ip_addr)
    {

        if (!$this->allow_multi_accounts) {

            $createAt = time() - 2 * 3600; // 2 hours

            $stmt = $this->db->prepare("SELECT id FROM users WHERE ip_addr = (:ip_addr) AND regtime > (:regtime) LIMIT 1");
            $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
            $stmt->bindParam(":regtime", $createAt, PDO::PARAM_INT);

            if ($stmt->execute()) {

                if ($stmt->rowCount() > 0) {

                    return true;
                }
            }
        }

        return false;
    }

    public function setPassword($password, $newPassword)
    {
        $result = array(
            'error' => true,
            'error_code' => ERROR_UNKNOWN
        );

        if (!helper::isCorrectPassword($password)) {

            return $result;
        }

        if (!helper::isCorrectPassword($newPassword)) {

            return $result;
        }

        $stmt = $this->db->prepare("SELECT salt FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $row = $stmt->fetch();
            $passw_hash = md5(md5($password).$row['salt']);

            $stmt2 = $this->db->prepare("SELECT id FROM users WHERE id = (:accountId) AND passw = (:password) LIMIT 1");
            $stmt2->bindParam(":accountId", $this->id, PDO::PARAM_INT);
            $stmt2->bindParam(":password", $passw_hash, PDO::PARAM_STR);
            $stmt2->execute();

            if ($stmt2->rowCount() > 0) {

                $this->newPassword($newPassword);

                $result = array(
                    "error" => false,
                    "error_code" => ERROR_SUCCESS
                );
            }
        }

        return $result;
    }

    public function createPassword($login, $password)
    {

        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $helper = new helper($this->db);

        if (!helper::isCorrectLogin($login)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_UNKNOWN,
                "error_type" => 0,
                "error_description" => "Incorrect login"
            );

            return $result;
        }

        if ($helper->isLoginExists($login)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_LOGIN_TAKEN,
                "error_type" => 0,
                "error_description" => "Login already taken"
            );

            return $result;
        }

        if (!helper::isCorrectPassword($password)) {

            $result = array(
                "error" => true,
                "error_code" => ERROR_UNKNOWN,
                "error_type" => 1,
                "error_description" => "Incorrect password"
            );

            return $result;
        }

        $salt = helper::generateSalt(3);
        $passw_hash = md5(md5($password).$salt);

        $stmt = $this->db->prepare("UPDATE users SET login = (:login), passw = (:passw), salt = (:salt), account_free = 0 WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":login", $login, PDO::PARAM_STR);
        $stmt->bindParam(":passw", $passw_hash, PDO::PARAM_STR);
        $stmt->bindParam(":salt", $salt, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                "error" => false,
                'error_code' => ERROR_SUCCESS,
                'error_description' => 'Success!'
            );

            return $result;
        }

        return $result;
    }

    public function newPassword($password)
    {
        $newSalt = helper::generateSalt(3);
        $newHash = md5(md5($password).$newSalt);

        $stmt = $this->db->prepare("UPDATE users SET passw = (:newHash), salt = (:newSalt) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":newHash", $newHash, PDO::PARAM_STR);
        $stmt->bindParam(":newSalt", $newSalt, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function setSex($sex)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET sex = (:sex) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":sex", $sex, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getSex()
    {
        $stmt = $this->db->prepare("SELECT sex FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['sex'];
        }

        return 0;
    }

    public function setAge($age)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET age = (:age) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":age", $age, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getAge()
    {
        $stmt = $this->db->prepare("SELECT age FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['age'];
        }

        return 18;
    }

    public function setAllowShowMyAgeAndGender($allowShowMyAgeAndGender)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET allowShowMyAgeAndGender = (:allowShowMyAgeAndGender) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":allowShowMyAgeAndGender", $allowShowMyAgeAndGender, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getAllowShowMyAgeAndGender()
    {
        $stmt = $this->db->prepare("SELECT age FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['allowShowMyAgeAndGender'];
        }

        return 0;
    }

    public function setBirth($year, $month, $day)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET bYear = (:bYear), bMonth = (:bMonth), bDay = (:bDay) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":bYear", $year, PDO::PARAM_INT);
        $stmt->bindParam(":bMonth", $month, PDO::PARAM_INT);
        $stmt->bindParam(":bDay", $day, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function setAdmob($admob)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $admob_create_at = 0;

        if ($admob != 0) {

            $admob_create_at = time();
        }

        $stmt = $this->db->prepare("UPDATE users SET admob = (:admob), admob_create_at = (:admob_create_at) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":admob", $admob, PDO::PARAM_INT);
        $stmt->bindParam(":admob_create_at", $admob_create_at, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setGhost($ghost)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $ghost_create_at = 0;

        if ($ghost != 0) {

            $ghost_create_at = time();
        }

        $stmt = $this->db->prepare("UPDATE users SET ghost = (:ghost), ghost_create_at = (:ghost_create_at) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":ghost", $ghost, PDO::PARAM_INT);
        $stmt->bindParam(":ghost_create_at", $ghost_create_at, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setPro($pro)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $pro_create_at = 0;

        if ($pro != 0) {

            $pro_create_at = time();
        }

        $stmt = $this->db->prepare("UPDATE users SET pro = (:pro), pro_create_at = (:pro_create_at) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":pro", $pro, PDO::PARAM_INT);
        $stmt->bindParam(":pro_create_at", $pro_create_at, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function setGoogleFirebaseId($gl_id)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET gl_id = (:gl_id) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":gl_id", $gl_id, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getGoogleFirebaseId()
    {
        $stmt = $this->db->prepare("SELECT gl_id FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['gl_id'];
        }

        return 0;
    }

    public function setAppleId($uid)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET ap_id = (:uid) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":uid", $uid, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getAppleId()
    {
        $stmt = $this->db->prepare("SELECT ap_id FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['ap_id'];
        }

        return 0;
    }

    public function setFacebookId($fb_id)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET fb_id = (:fb_id) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":fb_id", $fb_id, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getFacebookId()
    {
        $stmt = $this->db->prepare("SELECT fb_id FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['fb_id'];
        }

        return 0;
    }

    public function setFacebookPage($fb_page)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET fb_page = (:fb_page) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":fb_page", $fb_page, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setInstagramPage($instagram_page)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET my_page = (:my_page) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":my_page", $instagram_page, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setEmail($email)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $helper = new helper($this->db);

        if (!helper::isCorrectEmail($email)) {

            return $result;
        }

        if ($helper->isEmailExists($email)) {

            return $result;
        }

        $stmt = $this->db->prepare("UPDATE users SET email = (:email) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setUsername($username)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $helper = new helper($this->db);

        if (!helper::isCorrectLogin($username)) {

            return $result;
        }

        if ($helper->isLoginExists($username)) {

            return $result;
        }

        $stmt = $this->db->prepare("UPDATE users SET login = (:login) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":login", $username, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function setLocation($location)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET country = (:country) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":country", $location, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setGeoLocation($lat, $lng): array
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET lat = (:lat), lng = (:lng) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":lat", $lat, PDO::PARAM_STR);
        $stmt->bindParam(":lng", $lng, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS,
                'lat' => $lat,
                'lng' => $lng
            );
        }

        return $result;
    }

    public function setStatus($status)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET status = (:status) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function restorePointCreate($email, $clientId)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $restorePointInfo = $this->restorePointInfo();

        if (!$restorePointInfo['error']) {

            return $restorePointInfo;
        }

        $currentTime = time();	// Current time

        $u_agent = helper::u_agent();
        $ip_addr = helper::ip_addr();

        $hash = md5(uniqid(rand(), true));

        $stmt = $this->db->prepare("INSERT INTO restore_data (accountId, hash, email, clientId, createAt, u_agent, ip_addr) value (:accountId, :hash, :email, :clientId, :createAt, :u_agent, :ip_addr)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":hash", $hash, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":clientId", $clientId, PDO::PARAM_INT);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":u_agent", $u_agent, PDO::PARAM_STR);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS,
                'accountId' => $this->id,
                'hash' => $hash,
                'email' => $email
            );
        }

        return $result;
    }

    public function restorePointInfo()
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("SELECT * FROM restore_data WHERE accountId = (:accountId) AND removeAt = 0 LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $row = $stmt->fetch();

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS,
                'accountId' => $row['accountId'],
                'hash' => $row['hash'],
                'email' => $row['email']
            );
        }

        return $result;
    }

    public function restorePointRemove()
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $removeAt = time();

        $stmt = $this->db->prepare("UPDATE restore_data SET removeAt = (:removeAt) WHERE accountId = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $removeAt, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                "error" => false,
                "error_code" => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function deactivation()
    {
        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS
        );

        $time = time();

        // Mark account as deleted by a user

        $this->setState(ACCOUNT_STATE_DISABLED);

        // Overwriting account data with random data

        $new_login = "id".helper::generateId(9);
        $new_email = $new_login."@deleted.com";

        $stmt = $this->db->prepare("UPDATE users SET fullname = (:login), email = (:email), login = (:login), otpPhone = '', otpVerified = 0, referrer = 0, fb_id = '', gl_id = '', ap_id = '', lowPhotoUrl = '', originPhotoUrl = '', normalPhotoUrl= '', bigPhotoUrl = '', originCoverUrl = '', normalCoverUrl = '', photoModerateAt = 0, coverModerateAt = 0, photoPostModerateAt = 0, coverPostModerateAt = 0 WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":login", $new_login, PDO::PARAM_STR);
        $stmt->bindParam(":email", $new_email, PDO::PARAM_STR);
        $stmt->execute();

        // Notifications

        $stmt = $this->db->prepare("DELETE FROM notifications WHERE notifyToId = (:notifyToId)");
        $stmt->bindParam(":notifyToId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("DELETE FROM notifications WHERE notifyFromId = (:notifyFromId)");
        $stmt->bindParam(":notifyFromId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        // Likes

        $stmt = $this->db->prepare("UPDATE likes SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Comments

        $stmt = $this->db->prepare("UPDATE comments SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Chats and Messages

        $stmt = $this->db->prepare("UPDATE chats SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE chats SET removeAt = (:removeAt) WHERE toUserId = (:toUserId) AND removeAt = 0");
        $stmt->bindParam(":toUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE messages SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE messages SET removeAt = (:removeAt) WHERE toUserId = (:toUserId) AND removeAt = 0");
        $stmt->bindParam(":toUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Gifts

        $stmt = $this->db->prepare("UPDATE gifts SET removeAt = (:removeAt) WHERE giftTo = (:giftTo) AND removeAt = 0");
        $stmt->bindParam(":giftTo", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE gifts SET removeAt = (:removeAt) WHERE giftFrom = (:giftFrom) AND removeAt = 0");
        $stmt->bindParam(":giftFrom", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Guests

        $stmt = $this->db->prepare("UPDATE guests SET removeAt = (:removeAt) WHERE guestId = (:guestId) AND removeAt = 0");
        $stmt->bindParam(":guestId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE guests SET removeAt = (:removeAt) WHERE guestTo = (:guestTo) AND removeAt = 0");
        $stmt->bindParam(":guestTo", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Friends | Followers

        $stmt = $this->db->prepare("UPDATE friends SET removeAt = (:removeAt) WHERE friend = (:friend) AND removeAt = 0");
        $stmt->bindParam(":friend", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE friends SET removeAt = (:removeAt) WHERE friendTo = (:friendTo) AND removeAt = 0");
        $stmt->bindParam(":friendTo", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("DELETE FROM profile_followers WHERE follower = (:follower)");
        $stmt->bindParam(":follower", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("DELETE FROM profile_followers WHERE follow_to = (:follow_to)");
        $stmt->bindParam(":follow_to", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        // Items

        $stmt = $this->db->prepare("UPDATE posts SET removeAt = (:removeAt), allowRestore = 0 WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Gallery

        $stmt = $this->db->prepare("UPDATE gallery SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Market

        $stmt = $this->db->prepare("UPDATE market_items SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        // Reports

        $stmt = $this->db->prepare("UPDATE reports SET removeAt = (:removeAt) WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE reports SET removeAt = (:removeAt) WHERE toUserId = (:toUserId) AND removeAt = 0");
        $stmt->bindParam(":toUserId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $time, PDO::PARAM_INT);
        $stmt->execute();

        return $result;
    }

    public function setLanguage($language)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET language = (:language) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":language", $language, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array(
                "error" => false,
                "error_code" => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getLanguage()
    {
        $stmt = $this->db->prepare("SELECT language FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['language'];
        }

        return 'en';
    }

    public function setVerify($verify)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET verify = (:verify) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":verify", $verify, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function setFullname($fullname)
    {
        if (strlen($fullname) == 0) {

            return;
        }

        $stmt = $this->db->prepare("UPDATE users SET fullname = (:fullname) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);

        $stmt->execute();
    }

    public function setBalance($balance)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET balance = (:balance) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":balance", $balance, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getBalance()
    {
        $stmt = $this->db->prepare("SELECT balance FROM users WHERE id = (:accountId) LIMIT 1");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['balance'];
        }

        return 0;
    }

    public function setAllowMessages($allowMessages)
    {
        $stmt = $this->db->prepare("UPDATE users SET allowMessages = (:allowMessages) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":allowMessages", $allowMessages, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getAllowMessages()
    {
        $stmt = $this->db->prepare("SELECT allowMessages FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['allowMessages'];
        }

        return 0;
    }

    public function setAllowComments($allowComments)
    {
        $stmt = $this->db->prepare("UPDATE users SET allowComments = (:allowComments) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":allowComments", $allowComments, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getAllowComments()
    {
        $stmt = $this->db->prepare("SELECT allowComments FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['allowComments'];
        }

        return 0;
    }

    public function setAllowGalleryComments($allowGalleryComments)
    {
        $stmt = $this->db->prepare("UPDATE users SET allowGalleryComments = (:allowGalleryComments) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":allowGalleryComments", $allowGalleryComments, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getAllowGalleryComments()
    {
        $stmt = $this->db->prepare("SELECT allowGalleryComments FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['allowGalleryComments'];
        }

        return 0;
    }

    public function setPrivacySettings($allowShowMyGallery, $allowShowMyGifts, $allowShowMyFriends, $allowShowMyInfo, $allowVideoCalls)
    {
        $stmt = $this->db->prepare("UPDATE users SET allowShowMyGallery = (:allowShowMyGallery), allowShowMyGifts = (:allowShowMyGifts), allowShowMyFriends = (:allowShowMyFriends), allowShowMyInfo = (:allowShowMyInfo), allowVideoCalls = (:allowVideoCalls)  WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":allowShowMyGallery", $allowShowMyGallery, PDO::PARAM_INT);
        $stmt->bindParam(":allowShowMyGifts", $allowShowMyGifts, PDO::PARAM_INT);
        $stmt->bindParam(":allowShowMyFriends", $allowShowMyFriends, PDO::PARAM_INT);
        $stmt->bindParam(":allowShowMyInfo", $allowShowMyInfo, PDO::PARAM_INT);
        $stmt->bindParam(":allowVideoCalls", $allowVideoCalls, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getPrivacySettings()
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("SELECT allowShowMyGallery, allowShowMyGifts, allowShowMyFriends, allowShowMyInfo, allowVideoCalls FROM users WHERE id = (:id)");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            $result = array(
                "error" => false,
                "error_code" => ERROR_SUCCESS,
                "allowShowMyGallery" => $row['allowShowMyGallery'],
                "allowShowMyGifts" => $row['allowShowMyGifts'],
                "allowShowMyFriends" => $row['allowShowMyFriends'],
                "allowShowMyInfo" => $row['allowShowMyInfo'],
                "allowVideoCalls" => $row['allowVideoCalls']
            );
        }

        return $result;
    }

    public function setState($accountState)
    {

        $stmt = $this->db->prepare("UPDATE users SET state = (:accountState) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":accountState", $accountState, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getState()
    {
        $stmt = $this->db->prepare("SELECT state FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $row = $stmt->fetch();

            return $row['state'];
        }

        return 0;
    }

    public function setMood($mood)
    {
        $result = array('error' => false,
                        'error_code' => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET mood = (:mood) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":mood", $mood, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function setLastActive()
    {
        $time = time();

        $stmt = $this->db->prepare("UPDATE users SET last_authorize = (:last_authorize) WHERE id = (:id)");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":last_authorize", $time, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function get()
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_ACCOUNT_ID
        );

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $notifications_count = 0;
                $messages_count = 0;
                $guests_count = 0;

                $current_time = time();

                // Online

                $online = false;

                if ($row['last_authorize'] != 0 && $row['last_authorize'] > ($current_time - 15 * 60)) {

                    $online = true;
                }

                // Ghost Feature

                $ghost_feature = 0;

                if ($row['ghost_create_at'] + 2592000 > $current_time) {

                    $ghost_feature = 1;
                }

                // Admob Feature

                $admob_feature = 0;

                if ($row['admob_create_at'] + 2592000 > $current_time) {

                    $admob_feature = 1;
                }

                //

                $time = new language($this->db);

                $result = array(
                    "error" => false,
                    "error_code" => ERROR_SUCCESS,
                    "id" => $row['id'],
                    "account_free" => $row['account_free'],
                    "access_level" => $row['access_level'],
                    "account_type" => $row['account_type'],
                    "accountType" => $row['account_type'],
                    "accountAuthor" => $row['account_author'],
                    "rating" => $row['rating'],
                    "mood" => $row['mood'],
                    "admob" => $admob_feature,
                    "admob_create_at" => $row['admob_create_at'],
                    "ghost" => $ghost_feature,
                    "ghost_create_at" => $row['ghost_create_at'],
                    "pro" => $row['pro'],
                    "pro_create_at" => $row['pro_create_at'],
                    "gcm" => $row['gcm'],
                    "balance" => $row['balance'],
                    "fb_id" => $row['fb_id'],
                    "gl_id" => $row['gl_id'],
                    "ap_id" => $row['ap_id'],
                    "state" => $row['state'],
                    "regtime" => $row['regtime'],
                    "ip_addr" => $row['ip_addr'],
                    "username" => $row['login'],
                    "fullname" => stripcslashes($row['fullname']),
                    "location" => stripcslashes($row['country']),
                    "status" => stripcslashes($row['status']),
                    "fb_page" => stripcslashes($row['fb_page']),
                    "instagram_page" => stripcslashes($row['my_page']),
                    "my_page" => stripcslashes($row['my_page']),
                    "verify" => $row['verify'],
                    "verified" => $row['verify'],
                    "otpPhone" => $row['otpPhone'],
                    "otpVerified" => $row['otpVerified'],
                    "email" => $row['email'],
                    "sex" => $row['sex'],
                    "age" => $row['age'],
                    "year" => $row['bYear'],
                    "month" => $row['bMonth'],
                    "day" => $row['bDay'],
                    "lat" => $row['lat'],
                    "lng" => $row['lng'],
                    "language" => $row['language'],
                    "lowPhotoUrl" => $row['lowPhotoUrl'],
                    "normalPhotoUrl" => $row['normalPhotoUrl'],
                    "bigPhotoUrl" => $row['normalPhotoUrl'],
                    "coverUrl" => $row['normalCoverUrl'],
                    "normalCoverUrl" => $row['normalCoverUrl'],
                    "originCoverUrl" => $row['originCoverUrl'],
                    "allowPhotosComments" => $row['allowPhotosComments'],
                    "allowVideoComments" => $row['allowVideoComments'],
                    "allowGalleryComments" => $row['allowGalleryComments'],
                    "allowComments" => $row['allowComments'],
                    "allowMessages" => $row['allowMessages'],
                    "allowLikesGCM" => $row['allowLikesGCM'],
                    "allowCommentsGCM" => $row['allowCommentsGCM'],
                    "allowFollowersGCM" => $row['allowFollowersGCM'],
                    "allowGiftsGCM" => $row['allowGiftsGCM'],
                    "allowMessagesGCM" => $row['allowMessagesGCM'],
                    "allowCommentReplyGCM" => $row['allowCommentReplyGCM'],
                    "allowVideoCalls" => $row['allowVideoCalls'],
                    "allowShowMyInfo" => $row['allowShowMyInfo'],
                    "allowShowMyVideos" => $row['allowShowMyVideos'],
                    "allowShowMyFriends" => $row['allowShowMyFriends'],
                    "allowShowMyPhotos" => $row['allowShowMyPhotos'],
                    "allowShowMyGallery" => $row['allowShowMyGallery'],
                    "allowShowMyGifts" => $row['allowShowMyGifts'],
                    "allowShowMyAgeAndGender" => $row['allowShowMyAgeAndGender'],
                    "lastNotifyView" => $row['last_notify_view'],
                    "lastGuestsView" => $row['last_guests_view'],
                    "lastFriendsView" => $row['last_friends_view'],
                    "lastFeedView" => $row['last_feed_view'],
                    "lastAuthorize" => $row['last_authorize'],
                    "lastAuthorizeDate" => date("Y-m-d H:i:s", $row['last_authorize']),
                    "lastAuthorizeTimeAgo" => $time->timeAgo($row['last_authorize']),
                    "postsCount" => $row['posts_count'],
                    "friendsCount" => $row['friends_count'],
                    "followersCount" => $row['followers_count'],
                    "likesCount" => $row['likes_count'],
                    "photosCount" => $row['photos_count'],
                    "galleryItemsCount" => $row['gallery_items_count'],
                    "videosCount" => $row['videos_count'],
                    "giftsCount" => $row['gifts_count'],
                    "referralsCount" => $row['referrals_count'],
                    "createAt" => $row['regtime'],
                    "createDate" => date("Y-m-d", $row['regtime']),
                    "online" => $online,
                    "guestsCount" => $guests_count,
                    "notificationsCount" => $notifications_count,
                    "messagesCount" => $messages_count,
                    "photoModerateAt" => $row['photoModerateAt'],
                    "photoModerateUrl" => $row['photoModerateUrl'],
                    "photoPostModerateAt" => $row['photoPostModerateAt'],
                    "coverModerateAt" => $row['coverModerateAt'],
                    "coverModerateUrl" => $row['coverModerateUrl'],
                    "coverPostModerateAt" => $row['coverPostModerateAt'],
                    "inBlackList" => false,
                    "blocked" => false,
                    "friend" => false,
                    "follow" => false,
                    "follower" => false
                );

                unset($time);
            }
        }

        return $result;
    }

    public function updateOtpVerification($otpPhoneNumber, $otpVerified)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET otpPhone = (:otpPhone), otpVerified = (:otpVerified) WHERE id = (:accountId)");
        $stmt->bindParam(":otpPhone", $otpPhoneNumber, PDO::PARAM_STR);
        $stmt->bindParam(":otpVerified", $otpVerified, PDO::PARAM_INT);
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function updateCounters()
    {

        $galleryItemsCount = $this->getGalleryItemsCount();
        $postsCount = $this->getPostsCount();
        $giftsCount = $this->getGiftsCount();
        $likesCount = $this->getLikesCount();
        $followingCount = $this->getFollowingCount();
        $followersCount = $this->getFollowersCount();
        $friendsCount = $this->getFriendsCount();

        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN
        );

        $stmt = $this->db->prepare("UPDATE users SET posts_count = (:posts_count), gallery_items_count = (:gallery_items_count), gifts_count = (:gifts_count), likes_count = (:likes_count), friends_count = (:friends_count), following_count = (:following_count), followers_count = (:followers_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":posts_count", $postsCount, PDO::PARAM_INT);
        $stmt->bindParam(":gallery_items_count", $galleryItemsCount, PDO::PARAM_INT);
        $stmt->bindParam(":gifts_count", $giftsCount, PDO::PARAM_INT);
        $stmt->bindParam(":likes_count", $likesCount, PDO::PARAM_INT);
        $stmt->bindParam(":friends_count", $friendsCount, PDO::PARAM_INT);
        $stmt->bindParam(":following_count", $followingCount, PDO::PARAM_INT);
        $stmt->bindParam(":followers_count", $followersCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array(
                'error' => false,
                'error_code' => ERROR_SUCCESS
            );
        }

        return $result;
    }

    public function getFriendsCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM friends WHERE friendTo = (:profileId) AND removeAt = 0");
        $stmt->bindParam(":profileId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function setFriendsCount($friendsCount)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET friends_count = (:friends_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":friends_count", $friendsCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getGalleryItemsCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM gallery WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function setGiftsCount($giftsCount)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET gifts_count = (:gifts_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":gifts_count", $giftsCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getGiftsCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM gifts WHERE giftTo = (:accountId) AND removeAt = 0");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function setFollowingCount($followingCount)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET following_count = (:following_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":following_count", $followingCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getFollowingCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM profile_followers WHERE follower = (:followerId) AND follow_type = 0");
        $stmt->bindParam(":followerId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function setFollowersCount($followersCount)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET followers_count = (:followers_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":followers_count", $followersCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getFollowersCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM profile_followers WHERE follow_to = (:follow_to)");
        $stmt->bindParam(":follow_to", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function setPostsCount($postsCount)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET posts_count = (:posts_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":posts_count", $postsCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getPostsCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE fromUserId = (:fromUserId) AND groupId = 0 AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function setLikesCount($likesCount)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("UPDATE users SET likes_count = (:likes_count) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":likes_count", $likesCount, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array('error' => false,
                            'error_code' => ERROR_SUCCESS);
        }

        return $result;
    }

    public function getLikesCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM likes WHERE toUserId = (:toUserId) AND removeAt = 0");
        $stmt->bindParam(":toUserId", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function setLastGuestsView()
    {
        $time = time();

        $stmt = $this->db->prepare("UPDATE users SET last_guests_view = (:last_guests_view) WHERE id = (:id)");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":last_guests_view", $time, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getLastGuestsView()
    {
        $stmt = $this->db->prepare("SELECT last_guests_view FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                return $row['last_guests_view'];
            }
        }

        $time = time();

        return $time;
    }

    public function setLastFriendsView()
    {
        $time = time();

        $stmt = $this->db->prepare("UPDATE users SET last_friends_view = (:last_friends_view) WHERE id = (:id)");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":last_friends_view", $time, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getLastFriendsView()
    {
        $stmt = $this->db->prepare("SELECT last_friends_view FROM users WHERE id = (:id) LIMIT 1");
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                return $row['last_friends_view'];
            }
        }

        $time = time();

        return $time;
    }

    public function edit($fullname)
    {
        $result = array("error" => true);

        $stmt = $this->db->prepare("UPDATE users SET fullname = (:fullname) WHERE id = (:accountId)");
        $stmt->bindParam(":accountId", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array("error" => false);
        }

        return $result;
    }

    public function setPhoto($array_data)
    {
        $stmt = $this->db->prepare("UPDATE users SET originPhotoUrl = (:originPhotoUrl), normalPhotoUrl = (:normalPhotoUrl), bigPhotoUrl = (:bigPhotoUrl), lowPhotoUrl = (:lowPhotoUrl), photoModerateUrl = '' WHERE id = (:account_id)");
        $stmt->bindParam(":account_id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":originPhotoUrl", $array_data['originPhotoUrl'], PDO::PARAM_STR);
        $stmt->bindParam(":normalPhotoUrl", $array_data['normalPhotoUrl'], PDO::PARAM_STR);
        $stmt->bindParam(":bigPhotoUrl", $array_data['bigPhotoUrl'], PDO::PARAM_STR);
        $stmt->bindParam(":lowPhotoUrl", $array_data['lowPhotoUrl'], PDO::PARAM_STR);

        $stmt->execute();
    }

    public function setCover($array_data)
    {
        $stmt = $this->db->prepare("UPDATE users SET originCoverUrl = (:originCoverUrl), normalCoverUrl = (:normalCoverUrl), coverModerateUrl = '' WHERE id = (:account_id)");
        $stmt->bindParam(":account_id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":originCoverUrl", $array_data['originCoverUrl'], PDO::PARAM_STR);
        $stmt->bindParam(":normalCoverUrl", $array_data['normalCoverUrl'], PDO::PARAM_STR);

        $stmt->execute();
    }

    public function setCoverPosition($position)
    {
        $stmt = $this->db->prepare("UPDATE users SET coverPosition = (:coverPosition) WHERE id = (:account_id)");
        $stmt->bindParam(":account_id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":coverPosition", $position, PDO::PARAM_STR);

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
}

