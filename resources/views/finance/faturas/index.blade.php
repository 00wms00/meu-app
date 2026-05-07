@extends('layouts.app')
@section('title', 'Faturas')
@section('content')
@php
    $totalSemCartao = collect($semCartao)->sum('total');
@endphp

<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded text-xs text-red-800 space-y-1">
    <p><strong>debugTotal:</strong> {{ $debugTotal }}</p>
    <p><strong>IDs dos cartões ($cards):</strong>
        @foreach($cards as $c) {{ $c->id }} ({{ $c->nome }}) | @endforeach
    </p>
    <p><strong>Keys da matriz $faturas:</strong> {{ implode(', ', array_keys($faturas)) }}</p>
    <p><strong>credit_card_id das despesas:</strong>
        @foreach($debugDespesas as $d) {{ $d->credit_card_id ?? 'NULL' }} | @endforeach
    </p>
    <p><strong>mes_referencia das despesas:</strong>
        @foreach($debugDespesas as $d) {{ $d->mes_referencia }} | @endforeach
    </p>
</div>
@endsection
