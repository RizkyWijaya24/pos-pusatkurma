<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::create([
            'sku' => 'AJW-100',
            'name' => 'Kurma Ajwa',
            'category' => 'Kurma',
            'cost_price' => 100000,
            'selling_price' => 150000,
            'price_unit' => 'kg',
            'stock' => 50,
            'is_active' => true,
            'is_active_in_shop' => true,
        ]);
    }

    public function test_can_track_order_by_exact_order_code_via_get()
    {
        $order = Order::create([
            'order_code' => 'PK-ORD-20260806-TEST',
            'customer_name' => 'Rizky Wijaya',
            'customer_email' => 'rizky@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Cianjur No. 12',
            'total_amount' => 150000,
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'price' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->get(route('shop.track', ['order_code' => 'pk-ord-20260806-test']));

        $response->assertStatus(200)
            ->assertSee('PK-ORD-20260806-TEST')
            ->assertSee('Rizky Wijaya')
            ->assertSee('Pembayaran Lunas');
    }

    public function test_can_track_order_by_phone_number()
    {
        $order = Order::create([
            'order_code' => 'PK-ORD-PHONE-001',
            'customer_name' => 'Siti Mulyati',
            'customer_email' => 'siti@example.com',
            'customer_phone' => '089876543210',
            'shipping_address' => 'Jl. Raya Bandung No. 5',
            'total_amount' => 150000,
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'price' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->get(route('shop.track', ['order_code' => '089876543210']));

        $response->assertStatus(200)
            ->assertSee('PK-ORD-PHONE-001')
            ->assertSee('Siti Mulyati')
            ->assertSee('Menunggu Pembayaran');
    }

    public function test_can_track_order_by_email()
    {
        $order = Order::create([
            'order_code' => 'PK-ORD-EMAIL-001',
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi.santoso@example.com',
            'customer_phone' => '08111222333',
            'shipping_address' => 'Jl. Sudirman No. 8',
            'total_amount' => 150000,
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'price' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->get(route('shop.track', ['order_code' => 'budi.santoso@example.com']));

        $response->assertStatus(200)
            ->assertSee('PK-ORD-EMAIL-001')
            ->assertSee('Budi Santoso');
    }

    public function test_displays_multiple_orders_when_search_matches_multiple()
    {
        Order::create([
            'order_code' => 'PK-ORD-MULT-001',
            'customer_name' => 'Pelanggan Setia',
            'customer_email' => 'setia@example.com',
            'customer_phone' => '08555444333',
            'shipping_address' => 'Alamat 1',
            'total_amount' => 150000,
            'payment_status' => 'paid',
        ]);

        Order::create([
            'order_code' => 'PK-ORD-MULT-002',
            'customer_name' => 'Pelanggan Setia',
            'customer_email' => 'setia@example.com',
            'customer_phone' => '08555444333',
            'shipping_address' => 'Alamat 2',
            'total_amount' => 300000,
            'payment_status' => 'pending',
        ]);

        $response = $this->get(route('shop.track', ['order_code' => '08555444333']));

        $response->assertStatus(200)
            ->assertSee('Ditemukan 2 Pesanan untuk Kontak Ini')
            ->assertSee('PK-ORD-MULT-001')
            ->assertSee('PK-ORD-MULT-002');
    }

    public function test_shows_error_when_order_not_found()
    {
        $response = $this->post(route('shop.track.search'), ['order_code' => 'PK-INVALID-CODE']);

        $response->assertStatus(200)
            ->assertSee('tidak ditemukan', false);
    }

    public function test_admin_can_update_order_progress_status()
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $order = Order::create([
            'order_code' => 'PK-ORD-PROGRESS-01',
            'customer_name' => 'Budi Progress',
            'customer_email' => 'budi.prog@example.com',
            'customer_phone' => '08777666555',
            'shipping_address' => 'Jl. Kebon Jeruk No. 1',
            'total_amount' => 150000,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        // 1. Update status to 'processing' -> auto sets payment_status to 'paid' and step to 3
        $this->actingAs($admin)
            ->postJson(route('admin.orders.update-status', $order), ['status' => 'processing'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'new_status' => 'processing',
                'payment_status' => 'paid',
                'step_number' => 3,
            ]);

        $this->assertEquals('processing', $order->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);

        // 2. Update status to 'shipped' -> step to 4
        $this->actingAs($admin)
            ->postJson(route('admin.orders.update-status', $order), ['status' => 'shipped'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'new_status' => 'shipped',
                'step_number' => 4,
            ]);

        // 3. Check public tracking page shows 'Dikirim'
        $this->get(route('shop.track', ['order_code' => 'PK-ORD-PROGRESS-01']))
            ->assertStatus(200)
            ->assertSee('Dikirim');
    }
}
