@php
    use App\Support\RawMaterialUnit;

    $units = config('raw_materials.units', []);
    $fieldName = $name ?? 'unit';
    $selected = $selected ?? old($fieldName);
    $selectedStr = $selected !== null && $selected !== '' ? (string) $selected : '';
    $resolved = $selectedStr !== '' ? RawMaterialUnit::resolveToCanonical($selectedStr) : null;
    $effectiveValue = $resolved ?? $selectedStr;
    $legacyLabel = null;
    if ($selectedStr !== '' && $resolved === null && ! array_key_exists($selectedStr, $units)) {
        $legacyLabel = $selectedStr.' (legacy — pick a standard unit when you can)';
    }
    $required = ($required ?? true) ? 'required' : '';
    $selectClass = $selectClass ?? 'form-select form-select-sm';
@endphp
<select name="{{ $fieldName }}" data-unit-alias-normalize
        class="{{ $selectClass }} @error($fieldName) is-invalid @enderror" {{ $required }}>
    <option value="">— Select unit —</option>
    @if ($legacyLabel)
        <option value="{{ $selectedStr }}" selected>{{ $legacyLabel }}</option>
    @endif
    @foreach ($units as $value => $label)
        <option value="{{ $value }}" @selected($legacyLabel === null && (string) $effectiveValue === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
@error($fieldName)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var aliases = @json(config('raw_materials.unit_aliases', []));
            var canonical = @json(array_keys(config('raw_materials.units', [])));
            function normalizeUnitSelect(sel) {
                var v = sel.value;
                if (!v || canonical.indexOf(v) !== -1) {
                    return;
                }
                var k = String(v).trim().toLowerCase();
                var mapped = aliases[k];
                if (mapped && canonical.indexOf(mapped) !== -1) {
                    sel.value = mapped;
                }
            }
            document.querySelectorAll('select[data-unit-alias-normalize]').forEach(normalizeUnitSelect);
        });
    </script>
@endonce
