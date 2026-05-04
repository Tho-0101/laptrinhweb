<?php
/**
 * Application Constants
 */

// Order Statuses
define('ORDER_STATUS_PENDING', 'pending');                    // Chờ xác nhận
define('ORDER_STATUS_CONFIRMED', 'confirmed');                // Đã xác nhận
define('ORDER_STATUS_DEPOSIT_PAID', 'deposit_paid');          // Đã cọc
define('ORDER_STATUS_PROCESSING', 'processing');              // Đang xử lý
define('ORDER_STATUS_SHIPPING', 'shipping');                  // Đang giao
define('ORDER_STATUS_COMPLETED', 'completed');                // Hoàn thành
define('ORDER_STATUS_CANCELLED', 'cancelled');                // Đã hủy

// Payment Statuses
define('PAYMENT_STATUS_UNPAID', 'unpaid');                    // Chưa thanh toán
define('PAYMENT_STATUS_PARTIALLY_PAID', 'partially_paid');    // Đã cọc
define('PAYMENT_STATUS_PAID', 'paid');                        // Đã thanh toán
define('PAYMENT_STATUS_REFUNDED', 'refunded');                // Đã hoàn tiền

// Order Types
define('ORDER_TYPE_FULL_PAYMENT', 'full_payment');            // Thanh toán full
define('ORDER_TYPE_DEPOSIT', 'deposit');                      // Đặt cọc

// Delivery Methods
define('DELIVERY_METHOD_PICKUP', 'pickup');                   // Nhận tại chỗ
define('DELIVERY_METHOD_DELIVERY', 'delivery');               // Giao hàng

// Deposit Settings
define('MIN_DEPOSIT_PERCENT', 20);                            // Cọc tối thiểu 20%
define('MAX_DEPOSIT_DAYS', 7);                                // Thời hạn giữ cọc 7 ngày

// User Roles
define('ROLE_BUYER', 'buyer');
define('ROLE_SELLER', 'seller');
define('ROLE_INSPECTOR', 'inspector');
define('ROLE_ADMIN', 'admin');
define('ROLE_GUEST', 'guest');

// Bike Statuses
define('BIKE_STATUS_PENDING', 'pending');                     // Chờ duyệt
define('BIKE_STATUS_APPROVED', 'approved');                   // Đã duyệt
define('BIKE_STATUS_REJECTED', 'rejected');                   // Từ chối
define('BIKE_STATUS_SOLD', 'sold');                          // Đã bán
define('BIKE_STATUS_RESERVED', 'reserved');                   // Đã đặt cọc
define('BIKE_STATUS_DELETED', 'deleted');                     // Đã xóa
