@extends('layouts.app')
@section('title', 'Edit Customer')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">Edit Customer</div>
                <div class="report-panel-caption mono">{{ $customer->code }}</div>
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

        <form method="POST" action="{{ route('outbound.customers.update', $customer) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field">
                    <label for="code">Customer Code <span class="req">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code', $customer->code) }}" class="form-input mono" required>
                </div>
                <div class="form-field span-2">
                    <label for="name">Customer Name <span class="req">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" class="form-input" required>
                </div>
                <div class="form-field span-3">
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $customer->address) }}" class="form-input">
                </div>
                <div class="form-field">
                    <label for="phone">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="form-input">
                </div>
                <div class="form-field span-2">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" class="form-input">
                </div>
                <div class="form-field span-2">
                    <label for="contact_person">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $customer->contact_person) }}" class="form-input">
                </div>
                <div class="form-field">
                    <label for="is_active">Status</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', $customer->is_active))>Active</option>
                        <option value="0" @selected(!old('is_active', $customer->is_active))>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('outbound.customers.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
