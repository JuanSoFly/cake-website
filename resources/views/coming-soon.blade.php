@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-pink-800 mb-4">{{ $page }} Page Coming Soon</h1>
        <p class="text-gray-600 mb-6">We're working hard to bring you this feature. Please check back later!</p>
        <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
            Return to Home
        </a>
    </div>
</div>
@endsection
