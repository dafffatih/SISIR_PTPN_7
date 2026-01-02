@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  <h2 style="margin:0 0 8px;">Dashboard</h2>
  <p style="margin:0 0 18px; color:#475569;">
    Halo, <b>{{ auth()->user()->name }}</b> (role: <b>{{ auth()->user()->role }}</b>)
  </p>

  <div style="background:white; border-radius:14px; padding:18px; border:1px solid #e2e8f0;">
    <b>Dashboard Overview</b>
    <p style="margin:8px 0 0; color:#475569;">
      Ini halaman dashboard. Nanti kamu bisa isi card, chart, tabel sesuai desain yang kamu kirim.
    </p>
  </div>
@endsection
