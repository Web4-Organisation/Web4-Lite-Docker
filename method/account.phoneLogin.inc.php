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
 */;

if (!defined("APP_SIGNATURE")) {

    header("Location: /");
    exit;
}

require_once '../sys/addons/vendor/autoload.php';

use Kreait\Firebase\Factory;

use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\ServiceAccount;

if (!empty($_POST)) {

    $clientId = isset($_POST['client_id']) ? $_POST['client_id'] : 0;
    $appType = isset($_POST['app_type']) ? $_POST['app_type'] : 0; // 0 = APP_TYPE_UNKNOWN
    $lang = isset($_POST['lang']) ? $_POST['lang'] : '';
    $fcm_regId = isset($_POST['fcm_regId']) ? $_POST['fcm_regId'] : '';

    $idToken = isset($_POST['token']) ? $_POST['token'] : '';

    $clientId = helper::clearInt($clientId);

    $appType = helper::clearInt($appType);

    $lang = helper::clearText($lang);
    $lang = helper::escapeText($lang);

    $fcm_regId = helper::clearText($fcm_regId);
    $fcm_regId = helper::escapeText($fcm_regId);

    $result = array(
        "error" => true,
        "error_code" => ERROR_UNKNOWN,
        "desc" => "",
        "token" => "",
        "verified" => false
    );

    $jsonFileName = "";

    if ($files = glob("js/firebase/*.json")) {

        $jsonFileName = $files[0];
    }

    //$serviceAccount = ServiceAccount::fromValue($jsonFileName);

    $firebase = (new Factory)->withServiceAccount($jsonFileName);

    $firebaseAuth = $firebase->createAuth();

    try {

        $token = $firebaseAuth->verifyIdToken($idToken, true);

        $uid = $token->claims()->get('sub');

        $user = $firebaseAuth->getUser($uid);

        if ($user->phoneNumber != null) {

            $accountId = $helper->getUserIdByPhoneNumber($user->phoneNumber);

            if ($accountId == 0) {

                // Create new account

                $account = new account($dbo);
                $account_info = $account->signupOauth(OAUTH_TYPE_PHONE, "", "", "");
                unset($account);

                if (!$account_info['error']) {

                    $accountId = $account_info['accountId'];

                    $account = new account($dbo, $accountId);
                    $account->updateOtpVerification($user->phoneNumber, 1);
                    unset($account);
                }
            }

            if ($accountId != 0) {

                // authorize

                $account = new account($dbo, $accountId);

                $auth = new auth($dbo);
                $access_data = $auth->create($accountId, $clientId, $appType, $fcm_regId, $lang);

                if (!$access_data['error']) {

                    $account->setLastActive();

                    $result = array(
                        "error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "account_id" => $access_data['accountId'],
                        "access_token" => $access_data['accessToken'],
                    );

                    if ($appType == APP_TYPE_WEB) {

                        $accInfo = $account->get();

                        auth::setSession($accInfo['id'], $accInfo['username'], $accInfo['fullname'], $accInfo['lowPhotoUrl'], $accInfo['verified'], $accInfo['access_level'], $access_data['accessToken']);
                        auth::setCurrentUserOtpVerified($accInfo['otpVerified']);
                        auth::updateCookie($accInfo['username'], $access_data['accessToken']);
                    }
                }
            }
        }

        $firebaseAuth->revokeRefreshTokens($uid);

    } catch (InvalidToken $e) {

        $result['desc'] = "InvalidToken";
        $result['token'] = $e->getMessage();

    } catch (\Kreait\Firebase\Exception\AuthException $e) {

        $result['desc'] = "AuthException";
        $result['token'] = $e->getMessage();

    } catch (\Kreait\Firebase\Exception\FirebaseException $e) {

        $result['desc'] = "FirebaseException";
        $result['token'] = $e->getMessage();
    }

    echo json_encode($result);
    exit;
}
