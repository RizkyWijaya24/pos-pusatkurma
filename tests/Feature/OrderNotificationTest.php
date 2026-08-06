<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;
    private User $cashier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->cashier = User::factory()->create(['role' => 'kasir']);

        // Create Product
        $this->product = Product::create([
            'sku' => 'PK-TEST-NOTIF',
            'name' => 'Notif Test Product',
            'category' => 'kurma',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'price_unit' => 'pcs',
            'stock' => 10,
        ]);
    }

    /**
     * Test that storing a shop order dispatches notifications to admin and owner, but not cashiers.
     */
    public function test_storing_shop_order_creates_notifications(): void
    {
        // 1. Act: Post checkout request
        $response = $this->postJson('/shop/checkout', [
            'name' => 'Jane Doe',
            'phone' => '08123456789',
            'email' => 'jane@example.com',
            'address' => 'Test Address, Cianjur',
            'destination_city_id' => 82,
            'destination_city_name' => 'Cianjur',
            'shipping_courier' => 'jne',
            'shipping_service' => 'REG',
            'shipping_service_name' => 'JNE Reguler',
            'shipping_cost' => 10000,
            'shipping_etd' => '1-2 Hari',
            'items' => [
                [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'qty' => 2,
                    'price' => 15000,
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Get the created order
        $order = Order::latest()->first();
        $this->assertNotNull($order);

        // 2. Assert: Admin has the notification
        $adminNotifications = $this->admin->notifications;
        $this->assertCount(1, $adminNotifications);
        $this->assertEquals($order->id, $adminNotifications->first()->data['order_id']);
        $this->assertEquals('created', $adminNotifications->first()->data['action_type']);

        // Assert: Owner has the notification
        $ownerNotifications = $this->owner->notifications;
        $this->assertCount(1, $ownerNotifications);

        // Assert: Cashier has NO notification
        $cashierNotifications = $this->cashier->notifications;
        $this->assertCount(0, $cashierNotifications);
    }

    /**
     * Test that clicking a notification marks it as read and redirects.
     */
    public function test_reading_order_notification(): void
    {
        // 1. Create order and notification
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'customer_name' => 'Jane Doe',
            'customer_phone' => '08123456789',
            'customer_email' => 'jane@example.com',
            'shipping_address' => 'Test Address, Cianjur',
            'subtotal_amount' => 30000,
            'total_amount' => 40000,
            'payment_status' => 'pending',
        ]);

        $this->admin->notify(new \App\Notifications\OrderNotification($order, 'created'));
        $notification = $this->admin->unreadNotifications->first();
        $this->assertNotNull($notification);

        // 2. Act: Read notification via endpoint
        $response = $this->actingAs($this->admin)->get(route('notifications.read', $notification->id));

        // 3. Assert: Redirects to order details page
        $response->assertRedirect(route('admin.orders.show', $order->id));

        // Assert: Notification is marked as read
        $this->assertEquals(0, $this->admin->fresh()->unreadNotifications()->count());
    }

    /**
     * Test fetching the unread notifications count via JSON API.
     */
    public function test_fetching_unread_count_api(): void
    {
        // 1. Create order and notification
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'customer_name' => 'John Doe',
            'customer_phone' => '08123456780',
            'customer_email' => 'john@example.com',
            'shipping_address' => 'Test Address, Cianjur',
            'subtotal_amount' => 15000,
            'total_amount' => 25000,
            'payment_status' => 'pending',
        ]);

        $this->admin->notify(new \App\Notifications\OrderNotification($order, 'created'));

        // 2. Act: Fetch via endpoint
        $response = $this->actingAs($this->admin)->getJson(route('notifications.unread-count'));

        // 3. Assert: JSON structure and values
        $response->assertStatus(200)
            ->assertJsonStructure([
                'unread_count',
                'low_stock_count',
                'total'
            ])
            ->assertJson([
                'unread_count' => 1,
                'total' => 1
            ]);
    }
}
