{{-- Modal pemilih lokasi rak, visual 3D — dipakai bareng di Put Away & Picking.
     Butuh $locationCodes (array kode slot yang boleh dipilih) sudah ada di scope
     parent view. Klik tombol "Pick on 3D Rack" di baris manapun akan buka modal
     ini; klik slot yang berwarna hijau (ada di $locationCodes) untuk isi input
     lokasi baris itu. --}}
<div class="rack-picker-overlay" id="rackPickerOverlay" style="display:none;">
    <div class="rack-picker-modal">
        <div class="rack-picker-header">
            <div class="rack-picker-title">Pick a Rack Location</div>
            <button type="button" class="rack-picker-close" id="rackPickerClose">&times;</button>
        </div>
        <div class="rack-tabs no-print" id="rackPickerTabs"></div>
        <div id="rackPicker3d" class="rack-picker-3d"></div>
        <div class="rack-picker-legend">
            <span><span class="legend-dot" style="background:#22C55E"></span> {{ $pickerAvailableLabel ?? 'Available' }}</span>
            <span><span class="legend-dot" style="background:#CBD5E1"></span> Not available</span>
            <span class="rack-front-hint">Drag to rotate &middot; scroll to zoom &middot; click a green slot to select it</span>
        </div>
    </div>
</div>
