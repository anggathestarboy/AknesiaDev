<section class="w-full space-y-6">
    <div class="flex items-center flex-wrap justify-between border py-2 px-2 md:px-4 rounded-xl dark:border-zinc-500 overflow-hidden gap-3">
        <div class="flex gap-2 items-center">
            <flux:icon.cube class="w-6 h-6 text-gray-800 dark:text-white" />
            <flux:heading size="xl">{{ __("Package Updates") }}</flux:heading>
        </div>

        <div class="flex items-center gap-3">
            @if ($checkedAt)
                <flux:text size="sm" class="text-zinc-500">{{ __("Checked :time", ["time" => \Illuminate\Support\Carbon::parse($checkedAt)->diffForHumans()]) }}</flux:text>
            @endif
            <flux:button size="sm" class="cursor-pointer" icon="arrow-path" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">{{ __("Check now") }}</flux:button>
        </div>
    </div>

    @foreach ([["label" => __("Composer"), "icon" => "command-line", "updates" => $composerUpdates], ["label" => __("npm"), "icon" => "cube-transparent", "updates" => $npmUpdates]] as $group)
        <div class="bg-zinc-100 dark:bg-zinc-900 p-1.5 rounded-xl">
            <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-1.5 bg-zinc-100">
                    <flux:icon :icon="$group['icon']" class="w-4 h-4 text-zinc-500" />
                    <flux:heading size="sm">{{ $group["label"] }}</flux:heading>
                </div>

                <flux:table>
                    <flux:table.columns class="bg-zinc-100 dark:bg-zinc-900 [&>*]:text-white">
                        <flux:table.column>{{ __("Package") }}</flux:table.column>
                        <flux:table.column>{{ __("Current") }}</flux:table.column>
                        <flux:table.column>{{ __("Latest") }}</flux:table.column>
                        <flux:table.column>{{ __("Update type") }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($group["updates"] as $package)
                            <flux:table.row wire:key="{{ $group['label'] }}-{{ $package['name'] }}">
                                <flux:table.cell class="font-mono text-sm">{{ $package["name"] }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $package["current"] }}</flux:table.cell>
                                <flux:table.cell>{{ $package["latest"] }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match ($package['type']) {
        'major' => 'red',
        'minor' => 'amber',
        'patch' => 'green',
        default => 'zinc',
    }">{{ ucfirst($package["type"]) }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">{{ __("Everything is up to date.") }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endforeach
</section>
