@extends('layouts.app')
@section('title', 'Add Component')

@section('content')
    <a href="{{ route('master.components.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">Add Component</div>
            </div>
        </div>

        @if ($errors->any())
            <div class="note-danger">
                <ul style="margin:0;padding-left:18px;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('master.components.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label for="component_code">Component Code <span class="req">*</span></label>
                    <input type="text" name="component_code" id="component_code" value="{{ old('component_code') }}" class="form-input mono" required>
                </div>
                <div class="form-field span-2">
                    <label for="component_name">Component Name <span class="req">*</span></label>
                    <input type="text" name="component_name" id="component_name" value="{{ old('component_name') }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="default_uom">Default UOM <span class="req">*</span></label>
                    <input type="text" name="default_uom" id="default_uom" value="{{ old('default_uom', 'Pcs') }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="qty_label">Qty on Label <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0" name="qty_label" id="qty_label" value="{{ old('qty_label', 0) }}" class="form-input mono" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-in">Save Component</button>
                <a href="{{ route('master.components.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
