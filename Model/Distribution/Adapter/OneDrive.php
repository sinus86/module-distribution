<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the license that is available in LICENSE file.
 *
 * DISCLAIMER
 *
 * Do not edit this file if you wish to upgrade this extension to newer version in the future.
 */

namespace FjodorMaller\Distribution\Model\Distribution\Adapter;

use GuzzleHttp\ClientFactory;
use JacekBarecki\FlysystemOneDrive\Adapter\OneDriveAdapterFactory;
use JacekBarecki\FlysystemOneDrive\Client\OneDriveClientFactory;
use League\Flysystem\FilesystemFactory;

/**
 * Class OneDrive
 */
class OneDrive extends BaseAdapter
{
    /**
     * @var ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var FilesystemFactory
     */
    protected $_filesystemFactory;

    /**
     * @var OneDriveClientFactory
     */
    protected $_oneDriveClientFactory;

    /**
     * @var OneDriveAdapterFactory
     */
    protected $_oneDriveAdapterFactory;

    /**
     * @param ClientFactory          $clientFactory
     * @param FilesystemFactory      $filesystemFactory
     * @param OneDriveClientFactory  $oneDriveClientFactory
     * @param OneDriveAdapterFactory $oneDriveAdapterFactory
     *
     * @inheritdoc
     */
    public function __construct(
        ClientFactory $clientFactory,
        FilesystemFactory $filesystemFactory,
        OneDriveClientFactory $oneDriveClientFactory,
        OneDriveAdapterFactory $oneDriveAdapterFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_clientFactory          = $clientFactory;
        $this->_filesystemFactory      = $filesystemFactory;
        $this->_oneDriveClientFactory  = $oneDriveClientFactory;
        $this->_oneDriveAdapterFactory = $oneDriveAdapterFactory;
    }

    /**
     * @inheritdoc
     */
    public function getFormFields()
    {
        return [
            'field_access_token' => [
                'name'   => 'access_token',
                'config' => [
                    'label'       => __('Access Token'),
                    'value'       => '',
                    'formElement' => 'input',
                    'validation'  => [
                        'required-entry' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function createFilesystem(array $options = [])
    {
        $this->ensureRequiredOptions($options);
        $client     = $this->_oneDriveClientFactory->create([
            'accessToken' => $options[ 'access_token' ],
            'guzzle'      => $this->_clientFactory->create(),
        ]);
        $adapter    = $this->_oneDriveAdapterFactory->create([
            'client' => $client,
        ]);
        $filesystem = $this->_filesystemFactory->create([
            'adapter' => $adapter,
        ]);

        return $filesystem;
    }
}
