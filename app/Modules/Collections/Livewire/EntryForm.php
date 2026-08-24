<?php

namespace App\Modules\Collections\Livewire;

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Sites\Models\Site;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Full-page create/edit form for an entry, mirroring Statamic's CP routes:
 * create at collections/{collection}/entries/create/{site}?blueprint=...,
 * edit at collections/{collection}/entries/{entry}.
 */
class EntryForm extends Component
{
    const DEFAULT_SITE = 'indonesia';

    /**
     * @var array<string, string>
     */
    const STATUSES = [
        'published' => 'Published',
        'need_review' => 'Need Review',
        'draft' => 'Draft',
    ];

    public Collection $collection;

    public ?string $editingId = null;

    public string $site = self::DEFAULT_SITE;

    public string $title = '';

    public string $slug = '';

    public string $status = 'draft';

    public string $entryDate = '';

    public ?string $order = null;

    public string $blueprintHandle = '';

    /**
     * The entry this one is a localization of — self-referencing, so
     * switching sites can find (or create) the sibling entry for that site.
     */
    public ?string $originId = null;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(Collection $collection, ?Entry $entry = null, ?string $site = null): void
    {
        $this->collection = $collection;

        if ($entry) {
            if ($entry->collection !== $collection->handle) {
                throw new NotFoundHttpException;
            }

            $this->editingId = $entry->id;
            $this->site = $entry->site;
            $this->title = (string) ($entry->data['title'] ?? '');
            $this->slug = (string) $entry->slug;
            $this->status = $entry->status ?: 'draft';
            $this->entryDate = $entry->date ? str_replace(' ', 'T', $entry->date) : '';
            $this->order = $entry->order !== null ? (string) $entry->order : '';
            $this->blueprintHandle = $entry->blueprint ?? $this->defaultBlueprintHandle();
            $this->originId = $entry->origin_id;
        } else {
            $this->site = $site ?: self::DEFAULT_SITE;
            $this->blueprintHandle = request()->string('blueprint')->toString() ?: $this->defaultBlueprintHandle();
            $this->originId = request()->string('origin')->toString() ?: null;
        }

        $this->fillData($entry);
    }

    /**
     * Every configured site, with an active flag and the URL the sidebar
     * site-switcher should link to: the sibling entry's edit page if this
     * entry (or its origin) already has one for that site, otherwise a
     * create page pre-linked to this entry via ?origin=, mirroring
     * Statamic's per-site entry localization.
     */
    #[Computed]
    public function siteLinks(): array
    {
        return Site::orderBy('order')->get()
            ->map(function (Site $availableSite): array {
                $active = $availableSite->handle === $this->site;

                if ($active) {
                    $href = null;
                } elseif ($sibling = $this->siblingEntryForSite($availableSite->handle)) {
                    $href = route('collections.entries.edit', [
                        'collection' => $this->collection->handle,
                        'entry' => $sibling->id,
                    ]);
                } else {
                    $href = route('collections.entries.create', [
                        'collection' => $this->collection->handle,
                        'site' => $availableSite->handle,
                        'blueprint' => $this->blueprintHandle,
                        'origin' => $this->originId ?? $this->editingId,
                    ]);
                }

                return [
                    'handle' => $availableSite->handle,
                    'name' => $availableSite->name,
                    'active' => $active,
                    'href' => $href,
                ];
            })
            ->all();
    }

    protected function siblingEntryForSite(string $siteHandle): ?Entry
    {
        $originId = $this->originId ?? $this->editingId;

        if (! $originId) {
            return null;
        }

        return Entry::where('collection', $this->collection->handle)
            ->where('site', $siteHandle)
            ->where(fn ($query) => $query->where('id', $originId)->orWhere('origin_id', $originId))
            ->first();
    }

    /**
     * All blueprints an entry in this collection is allowed to use: the
     * collection's explicitly picked blueprint (if any) plus every
     * blueprint namespaced to this collection (e.g. "collections.pages").
     */
    #[Computed]
    public function availableBlueprints()
    {
        $explicitHandle = $this->collection->settings['blueprint'] ?? null;

        return Blueprint::where('namespace', "collections.{$this->collection->handle}")
            ->when($explicitHandle, fn ($query) => $query->orWhere('handle', $explicitHandle))
            ->orderBy('handle')
            ->get()
            ->unique('id')
            ->values();
    }

    #[Computed]
    public function selectedBlueprint(): ?Blueprint
    {
        return $this->availableBlueprints->firstWhere('handle', $this->blueprintHandle);
    }

    #[Computed]
    public function blueprintFields(): array
    {
        return $this->selectedBlueprint?->data['fields'] ?? [];
    }

    protected function defaultBlueprintHandle(): string
    {
        return $this->collection->effectiveBlueprint()?->handle
            ?? (string) $this->availableBlueprints->first()?->handle;
    }

    protected function fillData(?Entry $entry): void
    {
        $this->data = [];

        foreach ($this->blueprintFields() as $field) {
            $this->data[$field['handle']] = $entry?->data[$field['handle']]
                ?? $field['default']
                ?? ($field['type'] === 'toggle' ? false : '');
        }
    }

    /**
     * Switching which blueprint an entry uses changes what fields exist,
     * so any values already typed for the old blueprint's fields no longer
     * make sense — reset to the new blueprint's defaults.
     */
    public function updated(string $name): void
    {
        if ($name === 'blueprintHandle') {
            $this->fillData(null);
        }
    }

    /**
     * Build Laravel validation rules per blueprint field, using the field's
     * type, required flag, "sometimes" flag and custom rule string. Field
     * conditions (show/hide) are not evaluated here — see docs/AI-CONTEXT.md.
     */
    protected function fieldRules(): array
    {
        $rules = [];

        foreach ($this->blueprintFields() as $field) {
            $fieldRules = [];

            if ($field['validate_sometimes'] ?? false) {
                $fieldRules[] = 'sometimes';
            }

            $fieldRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';

            $fieldRules[] = match ($field['type']) {
                'number' => 'numeric',
                'toggle' => 'boolean',
                'date' => 'date',
                'select' => Rule::in($field['options'] ?? []),
                default => 'string',
            };

            if (! empty($field['validation'])) {
                $fieldRules[] = $field['validation'];
            }

            $rules["data.{$field['handle']}"] = $fieldRules;
        }

        return $rules;
    }

    public function save(): void
    {
        if ($this->order === '') {
            $this->order = null;
        }

        $validated = $this->validate(array_merge([
            'title' => ['required', 'string', 'max:300'],
            'slug' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'entryDate' => ['nullable', 'date'],
            'order' => ['nullable', 'integer'],
            'blueprintHandle' => ['required', Rule::in($this->availableBlueprints->pluck('handle')->all())],
        ], $this->fieldRules()));

        $data = $validated['data'] ?? [];
        $data['title'] = $validated['title'];

        Entry::updateOrCreate(['id' => $this->editingId], [
            'site' => $this->site,
            'origin_id' => $this->originId,
            'collection' => $this->collection->handle,
            'blueprint' => $validated['blueprintHandle'],
            'slug' => $validated['slug'] ?: null,
            'status' => $validated['status'],
            'published' => $validated['status'] === 'published',
            'date' => $validated['entryDate'] ? str_replace('T', ' ', $validated['entryDate']) : null,
            'order' => $validated['order'] !== null ? (int) $validated['order'] : null,
            'data' => $data,
        ]);

        Flux::toast(variant: 'success', text: __('Entry saved.'));

        $this->redirectRoute('collections.entries', ['collection' => $this->collection->handle], navigate: true);
    }

    public function render(): View
    {
        return view('collections::livewire.entry-form', [
            'statuses' => self::STATUSES,
        ])->title($this->editingId ? __('Edit entry') : __('New entry'));
    }
}
