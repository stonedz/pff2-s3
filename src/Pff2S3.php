<?php

namespace pff\modules;

use Aws\S3\S3Client;
use pff\Abs\AModule;
use pff\Abs\AView;
use pff\Core\ServiceContainer;
use pff\Iface\IConfigurableModule;

class Pff2S3 extends AModule implements IConfigurableModule {

    /**
     * Bucket name to use
     *
     * @var string
     */
    private $bucketName;

    /**
     * Amazon key
     *
     * @var string
     */
    private $awsKey;

    /**
     * Amazon password
     *
     * @var string
     */
    private $awsPass;

    /**
     * AWS region
     */
    private $region;

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var string
     */
    private $cloudfrontUrl;

    public function __construct($confFile = 'pff2-s3/module.conf.local.yaml') {
        $this->loadConfig($confFile);

        $this->s3Client = new S3Client(array(
            'credentials' => array(
                'key' => $this->awsKey,
                'secret' => $this->awsPass,
            ),
            'region' => $this->region,
            'version'=> '2006-03-01'
        ));
    }

    /**
     * @param array $parsedConfig
     * @return mixed
     */
    public function loadConfig($parsedConfig){
        $conf = $this->readConfig($parsedConfig);
        $this->bucketName = $conf['moduleConf']['bucketName'];
        $this->awsKey = $conf['moduleConf']['AWSKey'];
        $this->awsPass = $conf['moduleConf']['AWSPass'];
        $this->region = $conf['moduleConf']['AWSRegion'];
        $this->cloudfrontUrl = $conf['moduleConf']['AWSCloudfrontUrl'];
    }

    /**
     * @param $key string Name of the resource on S3
     * @return string
     */
    public function getContent($key) {
        $result = $this->s3Client->getObject(array(
            'Bucket' => $this->bucketName,
            'Key' => $key
        ));

        return $result['Body'];
    }

    /**
     * @param $key string Name of the resource on S3
     * @param $content Content of the file
     * @param bool $isPublic Set the resource to public
     * @return bool|\Guzzle\Service\Resource\Model
     */
    public function putFileContent($key, $content, $isPublic = false) {
        $options = array(
            'Bucket' => $this->bucketName,
            'Key'    => $key,
            'Body'   => $content
        );

        if($isPublic) {
            $options['ACL'] = 'public-read';
        }

        try{
            return $this->s3Client->putObject($options);
        }
        catch(\Exception $e) {
            return false;
        }

    }

    /**
     * @param $key string Name of the resource on S3
     * @param $localPath string Local path of the file you want to upload
     * @param bool $isPublic Set the resource to public
     * @return bool|\Guzzle\Service\Resource\Model
     */
    public function putFile($key, $localPath, $isPublic = false) {
        $options = array(
            'Bucket'     => $this->bucketName,
            'Key'        => $key,
            'SourceFile' => $localPath
        );

        if($isPublic) {
            $options['ACL'] = 'public-read';
        }
        try{
            return $this->s3Client->putObject($options);
        }
        catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Deletes an object given its key
     * @param $key Key name on S3 bucket
     * @return bool
     */
    public function deleteFile($key) {
        $res = $this->s3Client->deleteObject(array(
            'Bucket' => $this->bucketName,
            'Key' => $key
        ));
        if($res) {
            return true;
        }
        else {
            return false;
        }
    }

    public function uploadDir($loaclDirPath, $remotePath = null, $isPublic = false) {
        $options = array();
        if($isPublic) {
            $options['params']['ACL'] = 'public-read';
        }
        try {
            $ret = $this->s3Client->uploadDirectory($loaclDirPath, $this->bucketName, $remotePath, $options);
        }
        catch(\Exception $e) {
            throw $e;
            return false;
        }
        return $ret;

    }

    /**
     * Changes the target bucket
     *
     * @param $bucketName string
     */
    public function setBucket($bucketName) {
        $this->bucketName = $bucketName;
    }

    /**
     * @return string
     */
    public function getBucketName() {
        return $this->bucketName;
    }

    /**
     * @return string
     */
    public function getCloudfrontUrl() {
        return $this->cloudfrontUrl;
    }

    /**
     * @param string $cloudfrontUrl
     */
    public function setCloudfrontUrl($cloudfrontUrl) {
        $this->cloudfrontUrl = $cloudfrontUrl;
    }

}
