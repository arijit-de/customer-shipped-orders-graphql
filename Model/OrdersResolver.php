<?php
declare(strict_types=1);

namespace CodeTheatres\CustomerShippedOrdersGraphQl\Model;

use CodeTheatres\CustomerShippedOrdersGraphQl\Model\Resolver\ShippedOrderDataProvider;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Orders field resolver, used for GraphQL request processing.
 */
class OrdersResolver implements ResolverInterface
{
    /**
     * @var ShippedOrderDataProvider
     */
    private $customerShippedOrderResolver;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * OrdersResolver constructor.
     * @param ShippedOrderDataProvider $customerShippedOrderResolver
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        ShippedOrderDataProvider $customerShippedOrderResolver,
        CustomerFactory $customerFactory
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerShippedOrderResolver = $customerShippedOrderResolver;
    }

    /**
     * Get customer id from argument
     *
     * @param $args
     * @return int
     */
    private function getCustomerId($args)
    {
        if (!isset($args['customer_id'])) {
            throw new GraphQlInputException(__('"Customer id must be specified'));
        }

        return (int)$args['customer_id'];
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            $customerId = $this->getCustomerId($args);
            $data = $this->customerShippedOrderResolver->getOrdersByCustomerId($customerId);
            return !empty($data) ? $data : [];
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__('Customer id %1 does not exist.', [$customerId]));
        }
    }
}
