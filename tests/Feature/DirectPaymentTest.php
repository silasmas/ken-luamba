<?php

namespace Tests\Feature;

use App\Enums\BookFormatType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Enums\PricingPeriodType;
use App\Enums\UserRole;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\DirectPaymentSetting;
use App\Models\Order;
use App\Models\PricingPeriod;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests du parcours paiement direct et de la porte livreur.
 */
class DirectPaymentTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Prépare catalogue minimal + settings.
   *
   * @return BookFormat Format physique tarifé
   */
  private function seedCatalogBook(): BookFormat
  {
    ShopSetting::instance();
    DirectPaymentSetting::instance();

    $author = Author::query()->create([
      'full_name' => 'Ken Luamba',
      'slug' => 'ken-luamba',
      'is_published' => true,
      'is_primary' => true,
    ]);

    $book = Book::query()->create([
      'author_id' => $author->id,
      'title' => 'Livre Test',
      'slug' => 'livre-test',
      'is_published' => true,
      'published_at' => now()->subDay(),
    ]);

    $format = BookFormat::query()->create([
      'book_id' => $book->id,
      'type' => BookFormatType::Paperback,
      'stock_quantity' => 50,
      'is_active' => true,
    ]);

    PricingPeriod::query()->create([
      'book_format_id' => $format->id,
      'label' => 'Régulier',
      'type' => PricingPeriodType::Regular,
      'price' => 10000,
      'currency' => 'CDF',
      'start_at' => now()->subDay(),
      'end_at' => now()->addYear(),
      'is_active' => true,
    ]);

    return $format->fresh(['book', 'pricingPeriods']);
  }

  /**
   * Configure FlexPay carte pour les tests.
   */
  private function fakeFlexPayCard(): void
  {
    config([
      'services.flexpay.token' => 'test-token',
      'services.flexpay.merchant' => 'test-merchant',
      'services.flexpay.gateway_card' => 'https://flexpay.test/card',
    ]);

    Http::fake([
      'https://flexpay.test/card' => Http::response([
        'code' => '0',
        'url' => 'https://pay.example/redirect',
        'orderNumber' => 'FP-CARD-1',
      ], 200),
    ]);
  }

  /**
   * Le catalogue public expose le pack par défaut.
   */
  public function test_catalog_returns_default_pack(): void
  {
    $format = $this->seedCatalogBook();

    $response = $this->getJson('/api/v1/direct-payment/catalog');

    $response->assertOk()
      ->assertJsonPath('data.enabled', true)
      ->assertJsonPath('data.defaultSelectedIds.0', $format->id)
      ->assertJsonPath('data.books.0.bookFormatId', $format->id);
  }

  /**
   * Le checkout guest crée une commande source direct_payment et initie la carte.
   */
  public function test_checkout_creates_direct_payment_order_and_card_redirect(): void
  {
    $format = $this->seedCatalogBook();
    $this->fakeFlexPayCard();

    $response = $this->postJson('/api/v1/direct-payment/checkout', [
      'email' => 'acheteur@example.com',
      'bookFormatIds' => [$format->id],
      'channel' => PaymentChannel::Card->value,
    ]);

    $response->assertCreated()
      ->assertJsonPath('data.payment.type', 'card')
      ->assertJsonPath('data.payment.redirectUrl', 'https://pay.example/redirect');

    $order = Order::query()->where('order_number', $response->json('data.orderNumber'))->first();

    $this->assertNotNull($order);
    $this->assertTrue($order->isDirectPayment());
    $this->assertSame(OrderSource::DirectPayment, $order->source);
    $this->assertNotNull($order->public_token);
    $this->assertSame('acheteur@example.com', $order->user?->email);
    $this->assertSame(1, $order->items()->count());

    Http::assertSent(function ($request): bool {
      $body = $request->data();

      return str_contains((string) ($body['approve_url'] ?? ''), '/paiement-direct/result/')
        && str_contains((string) ($body['approve_url'] ?? ''), 'status=success');
    });
  }

  /**
   * La page résultat publique expose items, email et QR après paiement.
   */
  public function test_result_exposes_items_email_and_qr_after_payment(): void
  {
    Notification::fake();
    $format = $this->seedCatalogBook();
    $this->fakeFlexPayCard();

    $checkout = $this->postJson('/api/v1/direct-payment/checkout', [
      'email' => 'client@example.com',
      'bookFormatIds' => [$format->id],
      'channel' => PaymentChannel::Card->value,
    ])->assertCreated();

    $publicToken = $checkout->json('data.publicToken');
    $orderNumber = $checkout->json('data.orderNumber');

    app(PaymentService::class)->handleCardReturn($orderNumber, 'success');

    $response = $this->getJson('/api/v1/direct-payment/result/'.$publicToken);

    $response->assertOk()
      ->assertJsonPath('data.email', 'client@example.com')
      ->assertJsonPath('data.isPaid', true)
      ->assertJsonPath('data.items.0.bookTitle', 'Livre Test');

    $this->assertNotNull($response->json('data.qrToken'));
    $this->assertNotNull($response->json('data.scanUrl'));
  }

  /**
   * La porte livreur accepte un code valide et refuse un mauvais code.
   */
  public function test_courier_gate_login_and_confirm_flow(): void
  {
    Notification::fake();
    $format = $this->seedCatalogBook();
    $this->fakeFlexPayCard();

    $courier = User::query()->create([
      'name' => 'Livreur',
      'full_name' => 'Jean Livreur',
      'email' => 'livreur@test.com',
      'password' => Hash::make('password'),
      'role' => UserRole::Courier,
      'is_active' => true,
      'courier_code_hash' => Hash::make('CODE24H'),
    ]);

    $this->postJson('/api/v1/courier-gate/login', ['code' => 'WRONG'])
      ->assertStatus(422);

    $login = $this->postJson('/api/v1/courier-gate/login', ['code' => 'CODE24H'])
      ->assertOk();

    $token = $login->json('data.token');
    $this->assertNotEmpty($token);
    $this->assertNotNull($login->json('data.expiresAt'));

    $checkout = $this->postJson('/api/v1/direct-payment/checkout', [
      'email' => 'client2@example.com',
      'bookFormatIds' => [$format->id],
      'channel' => PaymentChannel::Card->value,
    ])->assertCreated();

    $orderNumber = $checkout->json('data.orderNumber');
    app(PaymentService::class)->handleCardReturn($orderNumber, 'success');

    $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();
    $qrToken = $order->qrCode?->token;
    $this->assertNotNull($qrToken);

    $this->withToken($token)
      ->postJson('/api/v1/courier-gate/scan', ['token' => $qrToken])
      ->assertOk()
      ->assertJsonPath('data.customerEmail', 'client2@example.com');

    $this->withToken($token)
      ->postJson('/api/v1/courier-gate/confirm', ['token' => $qrToken])
      ->assertOk()
      ->assertJsonPath('data.success', true);

    $order->refresh();
    $this->assertSame(OrderStatus::Completed, $order->status);
    $this->assertTrue((bool) $order->qrCode?->is_used);

    $this->withToken($token)
      ->postJson('/api/v1/courier-gate/confirm', ['token' => $qrToken])
      ->assertStatus(422);

    $this->withToken($token)
      ->postJson('/api/v1/courier-gate/logout')
      ->assertOk();
  }
}
