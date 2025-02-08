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

require '../sys/addons/vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class cdn extends db_connect
{
    private $ftp_url = "";
    private $ftp_server = "";
    private $ftp_user_name = "";
    private $ftp_user_pass = "";
    private $cdn_server = "";
    private $conn_id = false;

    public function __construct($dbo)
    {
        if (strlen($this->ftp_server) > 0) {

            $this->conn_id = @ftp_connect($this->ftp_server);
        }

        parent::__construct($dbo);
    }

    public function upload($file, $remote_file)
    {
        $remote_file = $this->cdn_server.$remote_file;

        if ($this->conn_id) {

            if (@ftp_login($this->conn_id, $this->ftp_user_name, $this->ftp_user_pass)) {

                // upload a file
                if (@ftp_put($this->conn_id, $remote_file, $file, FTP_BINARY)) {

                    return true;

                } else {

                    return false;
                }
            }
        }
    }

    public function uploadGalleryImage($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "gallery.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 2);

            if ($result['error']) {

                rename($imgFilename, GALLERY_PATH . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/" . GALLERY_PATH . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadPhoto($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "photo.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 0);

            if ($result['error']) {

                rename($imgFilename, PHOTO_PATH.basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL."/".PHOTO_PATH.basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadCover($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "cover.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 1);

            if ($result['error']) {

                rename($imgFilename, COVER_PATH . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/" . COVER_PATH . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadPostImg($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "post.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 4);

            if ($result['error']) {

                rename($imgFilename, POST_PHOTO_PATH . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/" . POST_PHOTO_PATH . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadChatImg($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "chat.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 6);

            if ($result['error']) {

                rename($imgFilename, CHAT_IMAGE_PATH . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/" . CHAT_IMAGE_PATH . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadVideoImg($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "thumbnail.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 3);

            if ($result['error']) {

                rename($imgFilename, VIDEO_IMAGE_PATH . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/" . VIDEO_IMAGE_PATH . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadVideo($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "video.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 3);

            if ($result['error']) {

                rename($imgFilename, VIDEO_PATH . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/" . VIDEO_PATH . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    public function uploadMarketImg($imgFilename)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "",
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $config = $settings->get();
        unset($settings);

        if ($config['S3_AMAZON']['intValue'] == 1) {

            $result = $this->s3_upload($config, "market.".$_SERVER['SERVER_NAME'], $imgFilename);
        }

        if ($result['error'] || strlen($result['fileUrl']) == 0) {

            // Google Cloud Storage

            $result = $this->upload_GCS($imgFilename, 5);

            if ($result['error']) {

                rename($imgFilename, "market/" . basename($imgFilename));

                $result['error'] = false;
                $result['fileUrl'] = APP_URL . "/market/" . basename($imgFilename);
                $result['error_description'] = "Default upload";
            }
        }

        @unlink($imgFilename);

        return $result;
    }

    private function s3_upload($settings, $bucket, $filename): array
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_SUCCESS,
            "error_description" => "s3 upload",
            "fileUrl" => ""
        );

        try {

            $s3 = new S3Client([

                'region' => $settings['S3_REGION']['textValue'],
                'version' => 'latest',
                'credentials' => [
                    'key'    => $settings['S3_KEY']['textValue'],
                    'secret' => $settings['S3_SECRET']['textValue']
                ]
            ]);

            try {

                // Test if bucket exists

                $response = $s3->headBucket(array('Bucket' => $bucket));

            } catch (Aws\S3\Exception\S3Exception $e) {

                // Create bucket

                try {

                    $response = $s3->createBucket(['Bucket' => $bucket]);

                    $result['error_description'] = 'The bucket\'s location is: ' . $response['Location'] . '. ' . 'The bucket\'s effective URI is: ' . $response['@metadata']['effectiveUri'];

                } catch (AwsException $e) {

                    $result['error_description'] = 'Error: ' . $e->getAwsErrorMessage();
                }
            }

            // Upload

            try {

                $response = $s3->putObject([

                    'Bucket' => $bucket,
                    'Key'    => basename($filename),
                    'Body'   => fopen($filename, 'r'),
                    'ACL'    => 'public-read'
                ]);

                $result['error'] = false;
                $result['error_description'] = "s3 upload";
                $result['fileUrl'] = $response['@metadata']['effectiveUri'];

            } catch (Aws\S3\Exception\S3Exception $e) {

                $result['error_description'] = "There was an error uploading the file.";
            }

        } catch (S3Exception $e) {

            $result['error_description'] = "unable connect to aws";
        }

        return $result;
    }

    public function upload_GCS($imgFilename, $type)
    {
        $result = array(
            "error" => true,
            "error_code" => ERROR_UNKNOWN,
            "error_description" => '',
            "fileUrl" => ""
        );

        $settings = new settings($this->db);
        $settings_result = $settings->get();
        unset($settings);

        $bucketName = "";

        switch ($type) {

            case 0: {

                if ($settings_result['gcs_photo']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_photo_bucket']['textValue'];
                }

                break;
            }

            case 1: {

                if ($settings_result['gcs_cover']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_cover_bucket']['textValue'];
                }

                break;
            }

            case 2: {

                if ($settings_result['gcs_gallery']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_gallery_bucket']['textValue'];
                }

                break;
            }

            case 3: {

                if ($settings_result['gcs_video']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_video_bucket']['textValue'];
                }

                break;
            }

            case 4: {

                if ($settings_result['gcs_item']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_item_bucket']['textValue'];
                }

                break;
            }

            case 5: {

                if ($settings_result['gcs_market']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_market_bucket']['textValue'];
                }

                break;
            }

            case 6: {

                if ($settings_result['gcs_chat']['intValue'] == 1) {

                    $bucketName = $settings_result['gcs_chat_bucket']['textValue'];
                }

                break;
            }

            default: {

                break;
            }
        }

        if (strlen($bucketName) != 0) {

            try {

                $jsonFileName = "";

                if ($files = glob("js/firebase/*.json")) {

                    $jsonFileName = $files[0];
                }

                $storage = new StorageClient([

                    'keyFilePath' => $jsonFileName
                ]);

                $bucket = $storage->bucket($bucketName);

                if (!$bucket->exists()) {

                    $storage->createBucket($bucketName);
                }

                $bucket = $storage->bucket($bucketName);
                $object = $bucket->upload(

                    fopen($imgFilename, 'r')
                );

                $object->update(['acl' => []], ['predefinedAcl' => 'PUBLICREAD']);

                $result['error'] = false;
                $result['error_code'] = ERROR_SUCCESS;
                $result['fileUrl'] = "https://storage.googleapis.com/".$bucketName."/".basename($imgFilename);

            } catch (Exception $e) {

                $result['error_description'] = $e->getMessage();
            }
        }

        return $result;
    }
}
