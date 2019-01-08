<?php
declare(strict_types=1);

namespace CodeTheatres\CustomerShippedOrdersGraphQl\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Customer field data provider, used for GraphQL request processing.
 */
class ShippedOrderDataProvider
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param SerializerInterface $jsonSerializer
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        ServiceOutputProcessor $serviceOutputProcessor,
        SerializerInterface $jsonSerializer,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->jsonSerializer = $jsonSerializer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get order data by Id or empty array
     *
     * @param int $customerId
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getOrdersByCustomerId(int $customerId) : array
    {
        try {
            $customerObject = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            // No error should be thrown, null result should be returned
            return [];
        }
        return $this->processOrders($customerObject);
    }

    /**
     * Transform single order data from object to in array format
     *
     * @param CustomerInterface $customerObject
     * @return array
     */
    private function processOrders(CustomerInterface $customerObject) : array
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_id',$customerObject->getId(),'eq')->create();
        $orders = $this->orderRepository->getList($searchCriteria);
        $result = [];
        foreach ($orders as $order) {
            $logger->info($order->getIncrementId());
            $result['items'][$order->getId()]['increment_id'] = $order->getIncrementId();
        }
        return $result;
    }
}
