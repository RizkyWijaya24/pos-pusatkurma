<?php

namespace Tests\Feature;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearUnpaidOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_unpaid_orders_command_deletes_old_pending_orders_only(): void
    {
        // 1. Create a pending (unpaid) order older than 24 hours (25 hours ago)
        $oldUnpaidOrder = Order::create([
            'order_code' => 'PK-ORD-OLD-UNPAID',
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'customer_email' => 'john@example.com',
            'shipping_address' => 'Jl. Test No. 12',
            'destination_city_id' => 1,
            'destination_city_name' => 'Cianjur',
            'shipping_courier' => 'JNE',
            'shipping_service' => 'REG',
            'shipping_service_name' => 'JNE Reguler',
            'shipping_cost' => 10000,
            'subtotal_amount' => 50000,
            'total_amount' => 60000,
            'payment_status' => 'pending',
        ]);
        // Manually update created_at via query builder to bypass timestamps auto-update
        \DB::table('orders')->where('id', $oldUnpaidOrder->id)->update([
            'created_at' => Carbon::now()->subHours(25),
        ]);

        // 2. Create a pending (unpaid) order newer than 24 hours (10 hours ago)
        $newUnpaidOrder = Order::create([
            'order_code' => 'PK-ORD-NEW-UNPAID',
            'customer_name' => 'Jane Doe',
            'customer_phone' => '081234567891',
            'customer_email' => 'jane@example.com',
            'shipping_address' => 'Jl. Test No. 13',
            'destination_city_id' => 1,
            'destination_city_name' => 'Cianjur',
            'shipping_courier' => 'JNE',
            'shipping_service' => 'REG',
            'shipping_service_name' => 'JNE Reguler',
            'shipping_cost' => 10000,
            'subtotal_amount' => 50000,
            'total_amount' => 60000,
            'payment_status' => 'pending',
        ]);
        \DB::table('orders')->where('id', $newUnpaidOrder->id)->update([
            'created_at' => Carbon::now()->subHours(10),
        ]);

        // 3. Create a paid order older than 24 hours (25 hours ago)
        $oldPaidOrder = Order::create([
            'order_code' => 'PK-ORD-OLD-PAID',
            'customer_name' => 'Bob Smith',
            'customer_phone' => '081234567892',
            'customer_email' => 'bob@example.com',
            'shipping_address' => 'Jl. Test No. 14',
            'destination_city_id' => 1,
            'destination_city_name' => 'Cianjur',
            'shipping_courier' => 'JNE',
            'shipping_service' => 'REG',
            'shipping_service_name' => 'JNE Reguler',
            'shipping_cost' => 10000,
            'subtotal_amount' => 50000,
            'total_amount' => 60000,
            'payment_status' => 'paid',
        ]);
        \DB::table('orders')->where('id', $oldPaidOrder->id)->update([
            'created_at' => Carbon::now()->subHours(25),
        ]);

        // Run the Artisan command
        $this->artisan('orders:clear-unpaid')
            ->expectsOutput('Successfully deleted 1 unpaid orders older than 24 hours.')
            ->assertExitCode(0);

        // Verify the database state
        $this->assertDatabaseMissing('orders', ['id' => $oldUnpaidOrder->id]);
        $this->assertDatabaseHas('orders', ['id' => $newUnpaidOrder->id]);
        $this->assertDatabaseHas('orders', ['id' => $oldPaidOrder->id]);
    }
}
