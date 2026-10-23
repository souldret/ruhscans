@extends('layouts.site')

@section('title', config('app.name').' — Türkçeleştirilmiş Ren\'Py oyunları')

@section('content')
    <x-hero :games="$featured" />
    <x-section-row title="Yeni Eklenenler" :games="$newest" />
    <x-section-row title="Popüler" :games="$popular" />
    <x-section-row title="VIP Özel" :games="$vip" />
    <x-section-row title="Güncellenenler" :games="$updated" />
@endsection
