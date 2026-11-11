<x-mail::message>
# Halo, {{ $order->user->name }}

{{ $intro }}

@if ($showTracking)
<x-mail::panel>
**Kurir:** {{ strtoupper($order->courier) }} {{ $order->courier_service }}<br>
**No. resi:** {{ $order->tracking_number }}
</x-mail::panel>
@endif

**No. pesanan:** {{ $order->code }}<br>
**Status:** {{ $order->status->label() }}

<x-mail::table>
| Barang | Jumlah | Harga |
|:-------|:------:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }} {{ $item->size_ml }}ml | {{ $item->qty }} | {{ rupiah($item->lineTotal()) }} |
@endforeach
| Subtotal | | {{ rupiah($order->subtotal) }} |
| Ongkir ({{ strtoupper($order->courier) }} {{ $order->courier_service }}) | | {{ rupiah($order->shipping_cost) }} |
@if ($order->discount > 0)
| Diskon | | −{{ rupiah($order->discount) }} |
@endif
| **Total** | | **{{ rupiah($order->total) }}** |
</x-mail::table>

**Dikirim ke**<br>
{{ $order->address_snapshot['recipient'] ?? '' }} ({{ $order->address_snapshot['phone'] ?? '' }})<br>
{{ $order->address_snapshot['detail'] ?? '' }}, {{ $order->address_snapshot['district_name'] ?? '' }}, {{ $order->address_snapshot['city_name'] ?? '' }}, {{ $order->address_snapshot['province_name'] ?? '' }} {{ $order->address_snapshot['postal_code'] ?? '' }}

<x-mail::button :url="route('orders.show', $order)">
Lihat pesanan
</x-mail::button>

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
