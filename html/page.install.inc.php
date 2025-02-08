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


    if (admin::isSession()) {

        header("Location: /");
        exit;
    }

    $admin = new admin($dbo);

    if ($admin->getCount()) {

        header("Location: /");
        exit;
    }

    $gift = new gift($dbo);
    $stickers = new sticker($dbo);

    include_once("../sys/core/initialize.inc.php");

    $page_id = "install";

    $itemId = 13965025; // Dating App Android = 14781822
                        // Dating App iOS = 19393764
                        // My Social Network Android = 13965025
                        // My Social Network iOS = 19414706

    $error = false;
    $error_message = array();

    $pcode = '';
    $user_username = '';
    $user_fullname = '';
    $user_password = '';
    $user_password_repeat = '';
    
    $error_token = false;
    $error_username = false;
    $error_fullname = false;
    $error_password = false;
    $error_password_repeat = false;

    if (!empty($_POST)) {

        $error = false;

        $pcode = isset($_POST['pcode']) ? $_POST['pcode'] : '';
        $user_username = isset($_POST['user_username']) ? $_POST['user_username'] : '';
        $user_password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
        $user_fullname = isset($_POST['user_fullname']) ? $_POST['user_fullname'] : '';
        $token = isset($_POST['authenticity_token']) ? $_POST['authenticity_token'] : '';

        $pcode = helper::clearText($pcode);
        $user_username = helper::clearText($user_username);
        $user_fullname = helper::clearText($user_fullname);
        $user_password = helper::clearText($user_password);
        $user_password_repeat = helper::clearText($user_password_repeat);

        $pcode = helper::escapeText($pcode);
        $user_username = helper::escapeText($user_username);
        $user_fullname = helper::escapeText($user_fullname);
        $user_password = helper::escapeText($user_password);
        $user_password_repeat = helper::escapeText($user_password_repeat);

        if (auth::getAuthenticityToken() !== $token) {

            $error = true;
            $error_token = true;
            $error_message[] = 'Error!';
        }

        $raccoonsquare_response = helper::verify_pcode($pcode, ENVATO_ITEM_ID);

        if (!is_array($raccoonsquare_response)) {

            $error = true;
            $error_message[] = 'Invalid response from verifying purchase code server. Try later.';
            $error_message[] = 'Check curl module - module must be installed and active.';

        } else {

            $raccoonsquare_response = json_decode(json_encode($raccoonsquare_response, JSON_FORCE_OBJECT));
        }

        if (!$error && $raccoonsquare_response->error) {

            $error = true;
            $error_message[] = 'Invalid response from verifying purchase code envato server. Try later.';
        }

        if (!$error && $raccoonsquare_response->error_code == ENVATO_ERROR_PCODE_INVALID) {

            $error = true;
            $error_message[] = 'Invalid purchase code.';
        }

        if (!$error && $raccoonsquare_response->error_code == ENVATO_ERROR_PCODE_ILLEGAL) {

            $error = true;
            $error_message[] = 'This purchase code is already in use for another domain.';
        }

        if (!$error && $raccoonsquare_response->error_code == ENVATO_ERROR_PCODE_UNKNOWN) {

            $error = true;
            $error_message[] = 'We were unable to verify this purchase code. Try later..';
        }

        if (!helper::isCorrectLogin($user_username)) {

            $error = true;
            $error_username = true;
            $error_message[] = 'Incorrect username.';
        }

        if (!helper::isCorrectPassword($user_password)) {

            $error = true;
            $error_password = true;
            $error_message[] = 'Incorrect password.';
        }

        if (!$error) {

            $admin = new admin($dbo);

            // Create admin account

            $result = array();
            $result = $admin->signup($user_username, $user_password, $user_fullname);

            if ($result['error'] === false) {

                $access_data = $admin->signin($user_username, $user_password);

                if ($access_data['error'] === false) {

                    $clientId = 0; // Desktop version

                    admin::createAccessToken();

                    admin::setSession($access_data['accountId'], admin::getAccessToken());

                    // Add standard settings

                    $settings = new settings($dbo);
                    $settings->createValue("admob", 1); //Default deactivated Disable Ads feature
                    $settings->createValue("allowMultiAccountsFunction", 1); //Default allow create multi-accounts
                    $settings->createValue("allowFacebookAuthorization", 1); //Default allow facebook authorization
                    $settings->createValue("photoModeration", 1); //Default on
                    $settings->createValue("coverModeration", 1); //Default on
                    $settings->createValue("defaultAllowMessages", 0); //Default off

                    $settings->createValue("interstitialAdAfterNewItem", 1);
                    $settings->createValue("interstitialAdAfterNewGalleryItem", 1);
                    $settings->createValue("interstitialAdAfterNewMarketItem", 1);
                    $settings->createValue("interstitialAdAfterNewLike", 5);

                    $settings->createValue("S3_AMAZON", 0);
                    $settings->createValue("S3_REGION", 0, "eu-north-1");
                    $settings->createValue("S3_KEY", 0, "");
                    $settings->createValue("S3_SECRET", 0, "");

                    $settings->createValue("RECAPTCHA_SIGNUP_APP", 0);

                    $settings->createValue("admin_account", 0, "");
                    $settings->createValue("admin_account_id", 0, "");
                    $settings->createValue("admin_account_allow_alerts", 0);

                    $settings->createValue("android_admob_app_id", 0, 'ca-app-pub-3940256099942544~3347511713');
                    $settings->createValue("android_admob_banner_ad_unit_id", 0, 'ca-app-pub-3940256099942544/6300978111');
                    $settings->createValue("android_admob_rewarded_ad_unit_id", 0, 'ca-app-pub-3940256099942544/5224354917');
                    $settings->createValue("android_admob_interstitial_ad_unit_id", 0, 'ca-app-pub-3940256099942544/1033173712');
                    $settings->createValue("android_admob_banner_native_ad_unit_id", 0, 'ca-app-pub-3940256099942544/2247696110');

                    $settings->createValue("ios_admob_app_id", 0, 'ca-app-pub-3940256099942544~3347511713');
                    $settings->createValue("ios_admob_banner_ad_unit_id", 0, 'ca-app-pub-3940256099942544/6300978111');
                    $settings->createValue("ios_admob_rewarded_ad_unit_id", 0, 'ca-app-pub-3940256099942544/5224354917');
                    $settings->createValue("ios_admob_interstitial_ad_unit_id", 0, 'ca-app-pub-3940256099942544/1033173712');
                    $settings->createValue("ios_admob_banner_native_ad_unit_id", 0, 'ca-app-pub-3940256099942544/2247696110');

                    $settings->createValue("gcv_adult", 0);
                    $settings->createValue("gcv_violence", 0);
                    $settings->createValue("gcv_racy", 0);
                    $settings->createValue("gcv_spoof", 0);
                    $settings->createValue("gcv_medical", 0);

                    $settings->createValue("gcs_photo", 0); // Disabled by default
                    $settings->createValue("gcs_cover", 0); // Disabled by default
                    $settings->createValue("gcs_gallery", 0); // Disabled by default
                    $settings->createValue("gcs_video", 0); // Disabled by default
                    $settings->createValue("gcs_item", 0); // Disabled by default
                    $settings->createValue("gcs_market", 0); // Disabled by default
                    $settings->createValue("gcs_chat", 0); // Disabled by default
                    $settings->createValue("gcs_auto_delete", 0);
                    $settings->createValue("gcs_photo_bucket", 0, "");
                    $settings->createValue("gcs_cover_bucket", 0, "");
                    $settings->createValue("gcs_gallery_bucket", 0, "");
                    $settings->createValue("gcs_video_bucket", 0, "");
                    $settings->createValue("gcs_item_bucket", 0, "");
                    $settings->createValue("gcs_market_bucket", 0, "");
                    $settings->createValue("gcs_chat_bucket", 0, "");

                    $settings->createValue("agora_app_enabled", 1, "");
                    $settings->createValue("agora_app_id", 0, "");
                    $settings->createValue("agora_app_certificate", 0, "");

                    // Phone Login

                    $settings->createValue("pl_enabled", 1, "");

                    unset($settings);

                    // Countries

                    $phone = new phone($dbo);

                    $c_list = $phone->c_getList(0);

                    if (count($c_list['items']) == 0){

                        $phone->c_add(32, "BE", "Belgium");
                        $phone->c_add(380, "UA", "Ukraine");
                        $phone->c_add(90, "TR", "Türkiye");
                        $phone->c_add(972, "IL", "Israel");
                        $phone->c_add(91, "US", "India");
                        $phone->c_add(62, "US", "Indonesia");
                        $phone->c_add(44, "US", "United Kingdom");
                        $phone->c_add(34, "US", "Spain");
                        $phone->c_add(41, "US", "Switzerland");
                        $phone->c_add(234, "US", "Nigeria");
                        $phone->c_add(49, "US", "Germany");
                        $phone->c_add(55, "US", "Brazil");
                        $phone->c_add(1, "US", "Canada");
                        $phone->c_add(82, "US", "South Korea");
                        $phone->c_add(156, "US", "China");
                    }

                    unset($phone);

                    // Add standard feelings

                    $feelings = new feelings($dbo);

                    if ($feelings->db_getMaxId() < 1) {

                        for ($i = 1; $i <= 12; $i++) {

                            $feelings->db_add(APP_URL."/feelings/".$i.".png");

                        }
                    }

                    unset($feelings);

                    // Add standard gifts

                    if ($gift->db_getMaxId() < 1) {

                        for ($i = 1; $i < 31; $i++) {

                            $gift->db_add(3, 0, APP_URL."/".GIFTS_PATH.$i.".jpg");

                        }
                    }

                    // Add standard stickers

                    if ($stickers->db_getMaxId() < 1) {

                        for ($i = 1; $i < 28; $i++) {

                            $stickers->db_add(APP_URL."/stickers/".$i.".png");

                        }
                    }

                    // Redirect to Admin Panel main page

                    header("Location: /admin/main");
                    exit;
                }

                header("Location: /install");
            }
        }
    }

    auth::newAuthenticityToken();

    $css_files = array();
    $page_title = APP_TITLE;

    include_once("../html/common/header.inc.php");
?>

<body class="remind-page has-bottom-footer">

    <?php

        include_once("../html/common/topbar.inc.php");
    ?>

    <div class="wrap content-page">
        <div class="main-column">
            <div class="main-content">

                <div class="standard-page">

                    <h1>Warning!</h1>
                    <p>Remember that now Create an account administrator!</p>

                    <div class="errors-container" style="<?php if (!$error) echo "display: none"; ?>">
                        <p class="title"><?php echo $LANG['label-errors-title']; ?></p>
                        <ul>
                            <?php

                            foreach ($error_message as $msg) {

                                echo "<li>{$msg}</li>";
                            }
                            ?>
                        </ul>
                    </div>

                    <form accept-charset="UTF-8" action="/install" class="custom-form" id="remind-form" method="post">

                        <input autocomplete="off" type="hidden" name="authenticity_token" value="<?php echo helper::getAuthenticityToken(); ?>">

                        <div class="row">
                            <div class="input-field col s12">
                                <p style="font-weight: 600">How to get purchase code you can read here: <a href="https://web4.one/help/how_to_get_purchase_code/" target="_blank">How to get purchase code?</a></p>
                            </div>
                        </div>

                        <input id="pcode" name="pcode" placeholder="Purchase code" required="required" size="60" type="text" value="<?php echo $pcode; ?>">

                        <input id="user_username" name="user_username" placeholder="Username" required="required" size="30" type="text" value="<?php echo $user_username; ?>">
                        <input id="user_fullname" name="user_fullname" placeholder="Fullname" required="required" size="30" type="text" value="<?php echo $user_fullname; ?>">
                        <input id="user_password" name="user_password" placeholder="Password" required="required" size="30" type="password" value="">

                        <div class="login-button">
                            <input class="button primary" name="commit" type="submit" value="Install">
                        </div>

                    </form>

                </div>

            </div>
        </div>

    </div>

    <?php

        include_once("../html/common/footer.inc.php");
    ?>

</body>
</html>