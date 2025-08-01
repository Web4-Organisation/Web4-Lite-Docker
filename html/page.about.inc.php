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

    $page_id = "about";

    $css_files = array("main.css", "my.css");
    $page_title = $LANG['page-about'];

    include_once("../html/common/header.inc.php");

    ?>

<body class="about has-bottom-footer">


    <?php
        include_once("../html/common/topbar.inc.php");
    ?>


    <div class="wrap content-page">

        <div class="main-column">

            <div class="main-content page-title-content p-0">

                <section class="standard-page">
                    <h1><?php echo $LANG['page-about']; ?></h1>
                    <p><?php echo APP_TITLE." ".APP_VERSION." (web version) © ".APP_YEAR; ?></p>
                </section>

                <section class="standard-page">
                    <h1>About Example Section title</h1>

                    <h3>About Example sub-title</h3>

                    <p>About Example text. About Example text. About Example text. About Example text. About Example text.</p>

                </section>

            </div>

        </div>

    </div>

    <?php

        include_once("../html/common/footer.inc.php");
    ?>


</body
</html>