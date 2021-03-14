<?php

namespace Sslcommerz\Payment\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;
use Sslcommerz\Payment\Gateway\Response\SuccessHandler;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Success extends \Magento\Framework\App\Action\Action
{
    protected $sslcommerznewData;
    protected $successHandler;
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    protected $_cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $_cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Sslcommerz\Payment\Model\Sslcommerznew $sslcommerznewData,
        SuccessHandler $successHandler,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->sslcommerznewData = $sslcommerznewData;
        $this->successHandler = $successHandler;
        $this->storeManager = $storeManager;
    }

    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        $currentStore = $this->storeManager->getStore();
        $baseUrl = $currentStore->getBaseUrl();
        $postResponse = $this->getRequest()->getPostValue();

        //var_dump($postResponse); die();

        if ($postResponse) {
           $getData = $this->successHandler->responseAction($postResponse);

        }
        $mail = $this->_objectManager->create('Sslcommerz\Payment\Controller\Payment\Sendemail');
        $whitelist = array('127.0.0.1', '::1');
        if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            $mail->SuccessEmail();
        }
        // $mail->SuccessEmail();
        return $this->resultPageFactory->create();


    }
}
