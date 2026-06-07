<?php

namespace Database\Seeders;

use App\Constants\PaymentGatewayCode;
use App\Models\PaymentGateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Configure real credentials in admin → Payment Gateways after seeding.
     */
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'Razorpay',
                'code' => PaymentGatewayCode::RAZORPAY,
                'is_active' => true,
                'credentials' => [
                    'key_id' => env('RAZORPAY_KEY_ID', ''),
                    'key_secret' => env('RAZORPAY_KEY_SECRET', ''),
                    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),
                    'env' => 'TEST',
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'PhonePe',
                'code' => PaymentGatewayCode::PHONEPE,
                'is_active' => false,
                'credentials' => [
                    'client_id' => env('PHONEPE_CLIENT_ID', ''),
                    'client_secret' => env('PHONEPE_CLIENT_SECRET', ''),
                    'client_version' => 1,
                    'webhook_username' => env('PHONEPE_WEBHOOK_USERNAME', ''),
                    'webhook_password' => env('PHONEPE_WEBHOOK_PASSWORD', ''),
                    'env' => 'UAT',
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'PayU',
                'code' => PaymentGatewayCode::PAYU,
                'is_active' => false,
                'credentials' => [
                    'key' => env('PAYU_KEY', ''),
                    'salt' => env('PAYU_SALT', ''),
                    'merchant_secret' => null,
                    'surl' => '',
                    'furl' => '',
                    'env' => 'TEST',
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'Cashfree',
                'code' => PaymentGatewayCode::CASHFREE,
                'is_active' => false,
                'credentials' => [
                    'app_id' => env('CASHFREE_APP_ID', ''),
                    'secret_key' => env('CASHFREE_SECRET_KEY', ''),
                    'env' => 'TEST',
                ],
                'sort_order' => 4,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }
    }
}
