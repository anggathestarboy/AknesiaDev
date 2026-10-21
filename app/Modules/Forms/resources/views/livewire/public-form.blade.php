<form wire:submit="submit" novalidate class="space-y-4">
    @if ($submitted)
        <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ __("Thank you — your message has been received.") }}
        </div>
    @endif

    @foreach ($this->visibleFields as $field)
        @php($fieldHandle = $field["handle"])
        <div>
            <label for="form-{{ $handle }}-{{ $fieldHandle }}" class="block text-sm font-medium text-zinc-700 mb-1.5">
                {{ ($field["display"] ?? "") ?: $fieldHandle }}
                @if ($field["required"] ?? false)
                    <span class="text-red-500">*</span>
                @endif
            </label>

            @if (! empty($field["instructions"]))
                <p class="text-xs text-zinc-500 mb-1.5">{{ $field["instructions"] }}</p>
            @endif

            @switch($field["type"])
                @case("textarea")
                    <textarea
                        id="form-{{ $handle }}-{{ $fieldHandle }}"
                        wire:model="data.{{ $fieldHandle }}"
                        rows="4"
                        placeholder="{{ $field["placeholder"] ?? "" }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-zinc-300 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-transparent resize-none"
                    ></textarea>
                    @break

                @case("select")
                    <select
                        id="form-{{ $handle }}-{{ $fieldHandle }}"
                        wire:model="data.{{ $fieldHandle }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-zinc-300 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-transparent bg-white"
                    >
                        <option value="">{{ __("Please choose") }}</option>
                        @foreach (($field["options"] ?? []) as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @break

                @case("radio")
                    <div class="space-y-1.5">
                        @foreach (($field["options"] ?? []) as $option)
                            <label for="form-{{ $handle }}-{{ $fieldHandle }}-{{ $loop->index }}" class="flex items-center gap-2 text-sm text-zinc-700">
                                <input
                                    id="form-{{ $handle }}-{{ $fieldHandle }}-{{ $loop->index }}"
                                    type="radio"
                                    wire:model="data.{{ $fieldHandle }}"
                                    value="{{ $option }}"
                                    class="text-zinc-900 focus:ring-zinc-900"
                                />
                                {{ $option }}
                            </label>
                        @endforeach
                    </div>
                    @break

                @case("checkbox")
                    <div class="space-y-1.5">
                        @foreach (($field["options"] ?? []) as $option)
                            <label for="form-{{ $handle }}-{{ $fieldHandle }}-{{ $loop->index }}" class="flex items-center gap-2 text-sm text-zinc-700">
                                <input
                                    id="form-{{ $handle }}-{{ $fieldHandle }}-{{ $loop->index }}"
                                    type="checkbox"
                                    wire:model="data.{{ $fieldHandle }}"
                                    value="{{ $option }}"
                                    class="text-zinc-900 focus:ring-zinc-900"
                                />
                                {{ $option }}
                            </label>
                        @endforeach
                    </div>
                    @break

                @case("toggle")
                    <label for="form-{{ $handle }}-{{ $fieldHandle }}" class="flex items-center gap-2 text-sm text-zinc-700">
                        <input
                            id="form-{{ $handle }}-{{ $fieldHandle }}"
                            type="checkbox"
                            wire:model="data.{{ $fieldHandle }}"
                            class="text-zinc-900 focus:ring-zinc-900"
                        />
                        {{ ($field["display"] ?? "") ?: $fieldHandle }}
                    </label>
                    @break

                @default
                    <input
                        id="form-{{ $handle }}-{{ $fieldHandle }}"
                        type="{{ $this->inputType($field) }}"
                        wire:model="data.{{ $fieldHandle }}"
                        placeholder="{{ $field["placeholder"] ?? "" }}"
                        @if ($this->inputType($field) === "email") autocomplete="email" @endif
                        class="w-full px-4 py-2.5 rounded-lg border border-zinc-300 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-transparent"
                    />
            @endswitch

            @error("data.".$fieldHandle)
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endforeach

    <button
        type="submit"
        wire:target="submit"
        wire:loading.attr="disabled"
        class="inline-flex items-center cursor-pointer justify-center px-8 py-3 rounded-[8px] bg-zinc-900 text-white font-semibold text-sm uppercase transition-all duration-300 hover:bg-zinc-700 disabled:opacity-60 disabled:cursor-default"
    >
        <span wire:loading.remove wire:target="submit">{{ __("Send") }}</span>
        <span wire:loading wire:target="submit">{{ __("Sending…") }}</span>
    </button>
</form>
