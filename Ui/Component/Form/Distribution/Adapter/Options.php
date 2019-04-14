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

namespace FjodorMaller\Distribution\Ui\Component\Form\Distribution\Adapter;

use FjodorMaller\Base\Api\OptionItemInterface;
use FjodorMaller\Distribution\Api\Data\Distribution\AdapterPoolInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var AdapterPoolInterface
     */
    protected $_pool;

    /**
     * @var array
     */
    protected $_options;

    /**
     * @param AdapterPoolInterface $pool
     */
    public function __construct(
        AdapterPoolInterface $pool
    ) {
        $this->_pool = $pool;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [];
            /* @var $type OptionItemInterface */
            foreach ($this->_pool->getAdapters() as $adapter) {
                $this->_options[] = [
                    'value' => $adapter->getCode(),
                    'label' => $adapter->getName(),
                ];
            }
        }

        return $this->_options;
    }
}
