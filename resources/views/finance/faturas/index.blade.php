@extends('layouts.app')
@section('title', 'Faturas')
@section('content')
@php
    // DEBUG TOTAL
    $totalSemCartao = collect($semCartao)->sum('total');
@endphp

<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded text-xs text-red-800 space-y-1">
    <p><strong>debugTotal (despesas encontradas):</strong> {{ $debugTotal }}</p>
    <p><strong>debugCartoes:</strong> {{ $debugCartoes }}</p>
    <p><strong>totalSemCartao:</strong> {{ $totalSemCartao }}</p>
    <p><strong>temSemCartao:</strong> {{ $temSemCartao ? 'true' : 'false' }}</p>
    <p><strong>semCartao keys:</strong> {{ implode(', ', array_keys($semCartao)) }}</p>
    <p><strong>semCartao totais:</strong>
        @foreach($semCartao as $k => $v) {{ $k }}: R${{ $v['total'] }} |  @endforeach
    </p>
    <p><strong>meses:</strong>
        @foreach($meses as $m) {{ $m->format('Y-m') }} | @endforeach
    </p>
</div>

<p class="text-sm text-gray-500">Veja o bloco vermelho acima e me informe os valores.</p>
@endsection
