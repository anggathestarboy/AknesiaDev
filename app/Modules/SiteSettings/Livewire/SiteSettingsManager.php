<?php

namespace App\Modules\SiteSettings\Livewire;

use App\Models\Taxonomy;
use App\Modules\Collections\Models\Collection;
use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Modules\SiteSettings\Services\LlmsTxtGenerator;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Site Settings')]
class SiteSettingsManager extends Component
{
    const SCHEMA_TYPES = ['Organization', 'WebSite', 'ContactPage', 'AboutPage', 'Service', 'CollectionPage'];

    /**
     * One entry per schema.org block to render (see SchemaMarkupBuilder) —
     * an admin can add more than one, e.g. Organization + WebSite together.
     * Each block always carries every field key (see defaultSchemaBlock()),
     * even the ones its own type doesn't use, so the form never has to
     * guess a missing array key.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $schemaBlocks = [];

    #[Url('q')]
    public string $activeTab = 'robots';

    public string $robotsTxt = '';

    public string $llmsTxt = '';

    /**
     * @var array<int, string>
     */
    public array $llmsCollections = [];

    /**
     * @var array<int, string>
     */
    public array $llmsTaxonomies = [];

    public int $llmsLimit = 1000;

    public string $llmsAdditionalContent = '';

    public bool $gaEnabled = false;

    public string $gaMeasurementId = '';

    public bool $gscEnabled = false;

    public string $gscVerificationCode = '';

    public function mount(): void
    {
        $settings = SiteSetting::current();

        $this->robotsTxt = (string) $settings->robots_txt;
        $this->llmsTxt = (string) $settings->llms_txt;
        $this->llmsCollections = $settings->llms_config['collections'] ?? [];
        $this->llmsTaxonomies = $settings->llms_config['taxonomies'] ?? [];
        $this->llmsLimit = $settings->llms_config['limit'] ?? 1000;
        $this->llmsAdditionalContent = (string) ($settings->llms_config['additional_content'] ?? '');
        $this->schemaBlocks = array_map(fn (array $block): array => array_merge($this->defaultSchemaBlock(), $block), $settings->schemaBlocks());
        $this->gaEnabled = $settings->googleAnalyticsEnabled();
        $this->gaMeasurementId = (string) $settings->googleAnalyticsMeasurementId();
        $this->gscEnabled = $settings->googleSearchConsoleEnabled();
        $this->gscVerificationCode = (string) $settings->googleSearchConsoleVerificationCode();
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSchemaBlock(string $type = 'Organization'): array
    {
        return [
            'type' => $type,
            'name' => '',
            'alternate_name' => '',
            'description' => '',
            'logo' => '',
            'same_as' => [],
            'email' => '',
            'telephone' => '',
            'contact_type' => '',
            'address_street' => '',
            'address_locality' => '',
            'address_region' => '',
            'address_postal' => '',
            'address_country' => '',
            'service_type' => '',
            'area_served' => '',
        ];
    }

    public function addSchemaBlock(): void
    {
        $this->schemaBlocks[] = $this->defaultSchemaBlock();
    }

    public function removeSchemaBlock(int $index): void
    {
        unset($this->schemaBlocks[$index]);
        $this->schemaBlocks = array_values($this->schemaBlocks);
    }

    #[Computed]
    public function availableCollections()
    {
        return Collection::orderBy('title')->get();
    }

    #[Computed]
    public function availableTaxonomies()
    {
        return Taxonomy::orderBy('title')->get();
    }

    public function generateLlms(): void
    {
        $this->llmsTxt = app(LlmsTxtGenerator::class)->generate($this->llmsCollections, $this->llmsTaxonomies, $this->llmsLimit, $this->llmsAdditionalContent);
    }

    public function toggleAllCollections(): void
    {
        $allHandles = $this->availableCollections->pluck('handle')->all();

        $this->llmsCollections = count($this->llmsCollections) === count($allHandles) ? [] : $allHandles;
    }

    public function toggleAllTaxonomies(): void
    {
        $allHandles = $this->availableTaxonomies->pluck('handle')->all();

        $this->llmsTaxonomies = count($this->llmsTaxonomies) === count($allHandles) ? [] : $allHandles;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function assetContainer(): ?string
    {
        return AssetContainer::orderBy('handle')->value('handle');
    }

    public function pickLogo(int $blockIndex): void
    {
        $this->dispatch('open-asset-picker', path: ['__site_settings_logo__', $blockIndex], container: $this->assetContainer, selected: []);
    }

    public function removeLogo(int $blockIndex): void
    {
        $this->schemaBlocks[$blockIndex]['logo'] = '';
    }

    #[On('assets-selected')]
    public function assetsSelected(array $path, array $ids): void
    {
        if (($path[0] ?? null) !== '__site_settings_logo__') {
            return;
        }

        $this->schemaBlocks[$path[1]]['logo'] = (string) (AssetMeta::whereIn('id', $ids)->first()?->url ?? '');
    }

    public function addSameAs(int $blockIndex): void
    {
        $this->schemaBlocks[$blockIndex]['same_as'][] = '';
    }

    public function removeSameAs(int $blockIndex, int $sameAsIndex): void
    {
        unset($this->schemaBlocks[$blockIndex]['same_as'][$sameAsIndex]);
        $this->schemaBlocks[$blockIndex]['same_as'] = array_values($this->schemaBlocks[$blockIndex]['same_as']);
    }

    public function fillDefaultRobots(): void
    {
        $this->robotsTxt = "User-agent: *\nAllow: /\nSitemap: ".rtrim(config('app.url'), '/').'/sitemap.xml';
    }

    public function fillDefaultLlms(): void
    {
        $this->llmsTxt = '# '.config('app.name')."\n\n> A short summary of what this site is about.\n\n## Pages\n\n- [Home](/): The homepage.\n";
    }

    public function save(): void
    {
        $validated = $this->validate([
            'robotsTxt' => ['nullable', 'string'],
            'llmsTxt' => ['nullable', 'string'],
            'llmsCollections' => ['array'],
            'llmsCollections.*' => ['string'],
            'llmsTaxonomies' => ['array'],
            'llmsTaxonomies.*' => ['string'],
            'llmsLimit' => ['required', 'integer', 'min:1'],
            'llmsAdditionalContent' => ['nullable', 'string'],
            'schemaBlocks' => ['array'],
            'schemaBlocks.*.type' => ['required', Rule::in(self::SCHEMA_TYPES)],
            'schemaBlocks.*.name' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.alternate_name' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.description' => ['nullable', 'string', 'max:1000'],
            'schemaBlocks.*.logo' => ['nullable', 'string', 'max:2048'],
            'schemaBlocks.*.same_as' => ['array'],
            'schemaBlocks.*.same_as.*' => ['nullable', 'string', 'max:2048'],
            'schemaBlocks.*.email' => ['nullable', 'email', 'max:255'],
            'schemaBlocks.*.telephone' => ['nullable', 'string', 'max:50'],
            'schemaBlocks.*.contact_type' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.address_street' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.address_locality' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.address_region' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.address_postal' => ['nullable', 'string', 'max:50'],
            'schemaBlocks.*.address_country' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.service_type' => ['nullable', 'string', 'max:255'],
            'schemaBlocks.*.area_served' => ['nullable', 'string', 'max:255'],
            'gaEnabled' => ['boolean'],
            'gaMeasurementId' => [Rule::requiredIf($this->gaEnabled), 'nullable', 'string', 'max:255'],
            'gscEnabled' => ['boolean'],
            'gscVerificationCode' => [Rule::requiredIf($this->gscEnabled), 'nullable', 'string', 'max:255'],
        ]);

        $schemaBlocks = array_map(fn (array $block): array => array_merge($block, ['same_as' => array_values(array_filter($block['same_as'] ?? []))]), $validated['schemaBlocks']);

        SiteSetting::current()->update([
            'robots_txt' => $validated['robotsTxt'] ?: null,
            'llms_txt' => $validated['llmsTxt'] ?: null,
            'llms_config' => [
                'collections' => $validated['llmsCollections'],
                'taxonomies' => $validated['llmsTaxonomies'],
                'limit' => $validated['llmsLimit'],
                'additional_content' => $validated['llmsAdditionalContent'] ?: null,
            ],
            'schema' => $schemaBlocks,
            'integrations' => [
                'ga_enabled' => $validated['gaEnabled'],
                'ga_measurement_id' => $validated['gaMeasurementId'] ?: null,
                'gsc_enabled' => $validated['gscEnabled'],
                'gsc_verification_code' => $validated['gscVerificationCode'] ?: null,
            ],
        ]);

        Flux::toast(variant: 'success', text: __('Site settings saved.'));
    }

    public function render(): View
    {
        return view('site-settings::livewire.site-settings-manager');
    }
}
