@extends('layouts.panel', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle])

@section('panel_content')
<div class="row g-3">
    @if(!empty($highlights))
        @foreach($highlights as $item)
            <div class="col-md-4">
                <div class="panel-card p-3 h-100">
                    <small class="text-muted">{{ $item['label'] }}</small>
                    <div class="h4 mb-1">{{ $item['value'] }}</div>
                    @if(!empty($item['note']))
                        <small class="text-muted">{{ $item['note'] }}</small>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <div class="col-12">
        <div class="panel-card p-4">
            <h6 class="fw-semibold mb-2">Deskripsi Modul</h6>
            <p class="text-muted mb-0">{{ $description }}</p>
        </div>
    </div>
</div>
@endsection
