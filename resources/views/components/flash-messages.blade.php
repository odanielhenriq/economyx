@if (session('success'))
    <div class="px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl" role="status">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl" role="alert">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
