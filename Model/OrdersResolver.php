<?php
declare(strict_types=1);

namespace CodeTheatres\CustomerShippedOrdersGraphQl\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use CodeTheatres\CustomerShippedOrdersGraphQl\Model\Resolver\ShippedOrderDataProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class OrdersResolver implements ResolverInterface
{
    /**
     * @var ShippedOrderDataProvider
     */
    private $customerShippedOrderResolver;

    /**
     * @param ShippedOrderDataProvider $customerShippedOrderResolver
     */
    public function __construct(
        ShippedOrderDataProvider $customerShippedOrderResolver
    ) {
        $this->customerShippedOrderResolver = $customerShippedOrderResolver;
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
        /** @var ContextInterface $context */
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                __(
                    'Current customer does not have access to the resource "%1"',
                    [\Magento\Customer\Model\Customer::ENTITY]
                )
            );
        }

        try {
            $data = $this->customerShippedOrderResolver->getOrdersByCustomerId($context->getUserId());
            return !empty($data) ? $data : [];
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__('Customer id %1 does not exist.', [$context->getUserId()]));
        }
    }
}
