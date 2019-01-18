<?php
declare(strict_types=1);

namespace CodeTheatres\CustomerShippedOrdersGraphQl\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;

/**
 * Customer shipped order data provider, used for GraphQL request processing.
 */
class ShippedOrderDataProvider
{
    const CUSTOMER_ID = "customer_id";
    const STATUS = "status";
    const STATE_PROCESSING = "processing";

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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * ShippedOrderDataProvider constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param SerializerInterface $jsonSerializer
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
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
    public function getOrdersByCustomerId(int $customerId): array
    {
        try {
            $customerObject = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __(
                    'Customer doesn\'t exist'
                )
            );
        }
        return $this->processOrders($customerObject);
    }

    /**
     * Transform single order data from object to in array format
     *
     * @param CustomerInterface $customerObject
     * @return array
     */
    private function processOrders(CustomerInterface $customerObject): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(self::CUSTOMER_ID, $customerObject->getId(), 'eq')
            ->addFilter(self::STATUS, self::STATE_PROCESSING, 'eq')
            ->create();
        $orders = $this->orderRepository->getList($searchCriteria);
        $result = [];
        foreach ($orders as $order) {
            $totalQtyOrdered = $order->getTotalQtyOrdered();
            $countItems = count($order->getAllItems());
            if ($countItems) {
                $shippedQty = 0;
                foreach ($order->getAllItems() as $item) {
                    $shippedQty += $item->getQtyShipped();
                }
            }

            if ($totalQtyOrdered != $shippedQty) {
                continue;
            }
            $result['items'][$order->getId()]['increment_id'] = $order->getIncrementId();
            $result['items'][$order->getId()]['customer_id'] = $order->getCustomerId();
            $result['items'][$order->getId()]['store_id'] = $order->getStoreId();
            $result['items'][$order->getId()]['created_at'] = $order->getCreatedAt();
            $result['items'][$order->getId()]['website_id'] = $order->getWebsiteId();
            $result['items'][$order->getId()]['customer_name'] = $order->getCustomerName();
            $result['items'][$order->getId()]['grand_total'] = $order->getGrandTotal();
            $result['items'][$order->getId()]['order_id'] = $order->getId();
        }
        return $result;
    }
}
