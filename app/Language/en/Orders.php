<?php

/**
 * Orders & Payments screens (index, show). Access with lang('Orders.key').
 */

return [
    // index
    'title'          => 'Orders & Payments',
    'subtitle'       => 'Every checkout from your sales page, with its payment status.',
    'paidRevenue'    => 'Paid Revenue',
    'paidOrders'     => 'Paid Orders',
    'pending'        => 'Pending',
    'failedCancelled'=> 'Failed / Cancelled',
    'searchPh'       => 'Search name, email or PayPal order id…',
    'allStatuses'    => 'All statuses',
    'stPaid'         => 'Paid',
    'stPending'      => 'Pending',
    'stFailed'       => 'Failed',
    'stCancelled'    => 'Cancelled',
    'filter'         => 'Filter',
    'clear'          => 'Clear',
    'thHash'         => '#',
    'thBuyer'        => 'Buyer',
    'thPlan'         => 'Plan',
    'thAmount'       => 'Amount',
    'thGateway'      => 'Gateway',
    'thStatus'       => 'Status',
    'thDate'         => 'Date',
    'thActions'      => 'Actions',
    'noOrders'       => 'No orders found.',
    'free'           => 'Free',
    'orders1'        => 'order',
    'ordersN'        => 'orders',

    // show
    'backToOrders'      => 'Back to Orders',
    'order'             => 'Order',
    'plan'              => 'Plan',
    'amount'            => 'Amount',
    'gateway'           => 'Gateway',
    'gatewayOrderId'    => 'Gateway Order ID',
    'created'           => 'Created',
    'lastUpdated'       => 'Last updated',
    'changeStatus'      => 'Change Payment Status',
    'confirmUpdateStatus'=> "Update this order's status?",
    'updateStatus'      => 'Update Status',
    'paidHintPre'       => 'Marking an unpaid order as',
    'paidHintPost'      => 'will create (or link) the tenant account and email their login details — use this for offline/bank-transfer payments.',
    'buyer'             => 'Buyer',
    'provisionedAccount'=> 'Provisioned Account',
    'viewTenant'        => 'View tenant',
    'noAccount'         => 'No account created for this order',
    'paymentNotCompleted'=> '(payment not completed)',
];
