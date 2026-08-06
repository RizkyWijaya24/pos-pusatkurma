<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->product = Product::create([
            'sku' => 'AJW-001',
            'name' => 'Kurma Ajwa Super',
            'category' => 'Kurma',
            'cost_price' => 100000,
            'selling_price' => 150000,
            'price_unit' => 'kg',
            'stock' => 50,
            'is_active' => true,
            'is_active_in_shop' => true,
        ]);
    }

    public function test_public_can_view_approved_reviews_only()
    {
        // Approved review
        ProductReview::create([
            'product_id' => $this->product->id,
            'reviewer_name' => 'Ahmad',
            'rating' => 5,
            'comment' => 'Sangat manis dan lembut!',
            'is_approved' => true,
        ]);

        // Unapproved review
        ProductReview::create([
            'product_id' => $this->product->id,
            'reviewer_name' => 'Budi',
            'rating' => 1,
            'comment' => 'Spam komentar',
            'is_approved' => false,
        ]);

        $response = $this->getJson(route('shop.product.reviews', $this->product));

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
                'avg_rating' => 5.0,
            ])
            ->assertJsonFragment(['reviewer_name' => 'Ahmad'])
            ->assertJsonMissing(['reviewer_name' => 'Budi']);
    }

    public function test_public_can_submit_unverified_review_without_order_code()
    {
        $response = $this->postJson(route('shop.review.store'), [
            'product_id' => $this->product->id,
            'reviewer_name' => 'Siti',
            'rating' => 4,
            'comment' => 'Kurmanya lezat sekali.',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $this->product->id,
            'reviewer_name' => 'Siti',
            'rating' => 4,
            'is_approved' => false,
            'order_code' => null,
        ]);
    }

    public function test_public_can_submit_verified_review_with_valid_paid_order()
    {
        $order = Order::create([
            'order_code' => 'PKM-20260806-TEST',
            'customer_name' => 'Pelanggan Setia',
            'customer_email' => 'pelanggan@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_city' => 'Bandung',
            'total_amount' => 150000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'price' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->postJson(route('shop.review.store'), [
            'product_id' => $this->product->id,
            'order_code' => 'pkm-20260806-test', // lowercased test
            'reviewer_name' => 'Pelanggan Setia',
            'rating' => 5,
            'comment' => 'Barang cepat sampai dan original!',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $this->product->id,
            'order_code' => 'PKM-20260806-TEST',
            'reviewer_name' => 'Pelanggan Setia',
            'rating' => 5,
            'is_approved' => false,
        ]);
    }

    public function test_submitting_review_fails_for_unpaid_order()
    {
        $order = Order::create([
            'order_code' => 'PKM-UNPAID-123',
            'customer_name' => 'Pelanggan Belum Bayar',
            'customer_email' => 'unpaid@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_city' => 'Bandung',
            'total_amount' => 150000,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'price' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->postJson(route('shop.review.store'), [
            'product_id' => $this->product->id,
            'order_code' => 'PKM-UNPAID-123',
            'reviewer_name' => 'Pelanggan Belum Bayar',
            'rating' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_submitting_review_fails_when_product_not_in_order()
    {
        $otherProduct = Product::create([
            'sku' => 'KSM-001',
            'name' => 'Kismis Simin',
            'category' => 'Olahan',
            'cost_price' => 30000,
            'selling_price' => 50000,
            'price_unit' => 'kg',
            'stock' => 20,
            'is_active' => true,
            'is_active_in_shop' => true,
        ]);

        $order = Order::create([
            'order_code' => 'PKM-OTHER-123',
            'customer_name' => 'Pelanggan Kismis',
            'customer_email' => 'kismis@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_city' => 'Bandung',
            'total_amount' => 50000,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $otherProduct->id,
            'qty' => 1,
            'price' => 50000,
            'subtotal' => 50000,
        ]);

        // Try reviewing $this->product which was not in $order
        $response = $this->postJson(route('shop.review.store'), [
            'product_id' => $this->product->id,
            'order_code' => 'PKM-OTHER-123',
            'reviewer_name' => 'Pelanggan Kismis',
            'rating' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_admin_can_view_moderate_approve_and_delete_reviews()
    {
        $review = ProductReview::create([
            'product_id' => $this->product->id,
            'reviewer_name' => 'Udin',
            'rating' => 5,
            'comment' => 'Ulasan bagus',
            'is_approved' => false,
        ]);

        // Admin index
        $this->actingAs($this->user)
            ->get(route('admin.reviews.index', ['status' => 'pending']))
            ->assertStatus(200)
            ->assertSee('Udin');

        // Admin approve
        $this->actingAs($this->user)
            ->postJson(route('admin.reviews.approve', $review))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue($review->fresh()->is_approved);

        // Admin delete
        $this->actingAs($this->user)
            ->deleteJson(route('admin.reviews.destroy', $review))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }
}
