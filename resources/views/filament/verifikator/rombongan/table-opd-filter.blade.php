<div class="flex items-center gap-3">
    <div class="w-56">
        <label class="text-sm font-medium dark:text-gray-300">
            Pilih OPD
        </label>

        <select
            wire:model.live="opdSelected"
            class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600"
        >
            <option value="">-- Pilih OPD --</option>

            @foreach ($opds as $code => $name)
                <option value="{{ $code }}">{{ $code }}</option>
            @endforeach
        </select>
    </div>
</div>
