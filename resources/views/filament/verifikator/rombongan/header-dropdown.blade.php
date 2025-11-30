<div class="mb-4 w-full">
    <label class="block text-sm font-medium mb-2">
        Pilih OPD
    </label>

    <select
        wire:model.live="opdSelected"
        class="w-64 rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600"
    >
        <option value="">-- Pilih OPD --</option>

        @foreach ($opds as $code => $name)
            <option value="{{ $code }}">{{ $code }}</option>
        @endforeach
    </select>

    @if (!$opdSelected)
        <div class="mt-3 p-3 bg-red-100 text-red-800 rounded-lg">
            Silahkan pilih OPD terlebih dahulu
        </div>
    @endif
</div>
