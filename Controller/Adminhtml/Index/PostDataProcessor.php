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

namespace FjodorMaller\Distribution\Controller\Adminhtml\Index;

use FjodorMaller\Distribution\Api\Data\DistributionInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class PostDataProcessor
 */
class PostDataProcessor
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
    }

    /**
     * Filters given data.
     *
     * @param array $data
     *
     * @return array
     */
    public function filter(array $data)
    {
        $filterRules = [];
        if (isset($data[ DistributionInterface::PARAM_NAME ])) {
            $data = $data[ DistributionInterface::PARAM_NAME ];
        }
        if (isset($data[ DistributionInterface::DISTRIBUTION_ID ]) &&
            !$data[ DistributionInterface::DISTRIBUTION_ID ]) {
            unset($data[ DistributionInterface::DISTRIBUTION_ID ]);
        }
        $data = $this->filterOptions($data);

        return (new \Zend_Filter_Input($filterRules, [], $data))->getUnescaped();
    }

    /**
     * Filters options by given data.
     *
     * @param array $data
     *
     * @return array
     */
    public function filterOptions(array $data)
    {
        if (isset($data[ DistributionInterface::OPTIONS ])) {
            $data[ DistributionInterface::OPTIONS ] = array_intersect_key(
                $data[ DistributionInterface::OPTIONS ],
                [
                    $data[ DistributionInterface::ADAPTER ] => true,
                ]
            );
        }

        return $data;
    }

    /**
     * Validates given data.
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate($data)
    {
        return $this->validateData($data);
    }

    /**
     * Validates required entries of given data.
     *
     * @param array $data
     *
     * @return bool
     */
    public function validateRequireEntry(array $data)
    {
        $error          = true;
        $requiredFields = [
            DistributionInterface::NAME      => __('Name'),
            DistributionInterface::IS_ACTIVE => __('Is Active'),
        ];
        foreach ($data as $field => $value) {
            if (in_array($field, array_keys($requiredFields)) && $value == '') {
                $error = false;
                $this->messageManager->addErrorMessage(
                    __('To apply changes you should fill in required "%1" field', $requiredFields[ $field ])
                );
            }
        }

        return $error;
    }

    /**
     * Validates given data.
     *
     * @param array $data
     *
     * @return bool
     */
    private function validateData($data)
    {
        return true;
    }
}