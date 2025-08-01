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

if (!empty($_POST)) {

    $number = isset($_POST['number']) ? $_POST['number'] : '';

    $number = helper::clearText($number);

    $result = array(
        "error" => true,
        "error_code" => ERROR_UNKNOWN
    );

    if (!$helper->isPhoneNumberExists($number)) {

        $result = array(
            "error" => false,
            "error_code" => ERROR_SUCCESS
        );
    }

    echo json_encode($result);
    exit;
}
