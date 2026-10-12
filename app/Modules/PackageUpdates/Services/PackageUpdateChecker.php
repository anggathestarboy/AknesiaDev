<?php

namespace App\Modules\PackageUpdates\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

/**
 * Checks the project's direct Composer and npm dependencies against their
 * latest published versions, and classifies each pending update as a
 * major/minor/patch bump by comparing version numbers (not by trusting
 * composer's own "latest-status", which reflects the version constraint in
 * composer.json rather than real semver distance).
 *
 * Results are cached (see CACHE_KEY) since both checks shell out to
 * composer/npm, which call out to packagist.org/registry.npmjs.org and can
 * take several seconds.
 *
 * @phpstan-type PackageUpdate array{name: string, current: string, latest: string, type: string}
 * @phpstan-type CheckResult array{checked_at: string, composer: array<int, PackageUpdate>, npm: array<int, PackageUpdate>}
 */
class PackageUpdateChecker
{
    const CACHE_KEY = 'package-updates:result';

    const CACHE_TTL = 3600;

    /**
     * @return CheckResult
     */
    public function cached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->check());
    }

    /**
     * @return CheckResult
     */
    public function refresh(): array
    {
        $result = $this->check();

        Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * @return CheckResult
     */
    protected function check(): array
    {
        return [
            'checked_at' => now()->toIso8601String(),
            'composer' => $this->composerUpdates(),
            'npm' => $this->npmUpdates(),
        ];
    }

    /**
     * @return array<int, PackageUpdate>
     */
    protected function composerUpdates(): array
    {
        $result = Process::path(base_path())->timeout(120)->run(['composer', 'show', '--direct', '--latest', '--format=json']);

        if (! $result->successful()) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $installed */
        $installed = json_decode($result->output(), true)['installed'] ?? [];

        return collect($installed)
            ->filter(fn (array $package): bool => ($package['latest-status'] ?? 'up-to-date') !== 'up-to-date')
            ->map(fn (array $package): array => [
                'name' => $package['name'],
                'current' => $package['version'],
                'latest' => $package['latest'],
                'type' => $this->bumpType($package['version'], $package['latest']),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, PackageUpdate>
     */
    protected function npmUpdates(): array
    {
        // npm exits with status 1 whenever outdated packages exist, so the
        // exit code can't be used to tell a real failure from "found some".
        $result = Process::path(base_path())->timeout(120)->run(['npm', 'outdated', '--json']);

        $data = json_decode($result->output(), true);

        if (! is_array($data)) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $data */
        return collect($data)
            ->filter(fn (array $package): bool => isset($package['current'], $package['latest']))
            ->map(fn (array $package, string $name): array => [
                'name' => $name,
                'current' => $package['current'],
                'latest' => $package['latest'],
                'type' => $this->bumpType($package['current'], $package['latest']),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    protected function bumpType(string $current, string $latest): string
    {
        $parse = function (string $version): array {
            preg_match('/(\d+)(?:\.(\d+))?(?:\.(\d+))?/', $version, $matches);

            return [(int) ($matches[1] ?? 0), (int) ($matches[2] ?? 0), (int) ($matches[3] ?? 0)];
        };

        [$currentMajor, $currentMinor, $currentPatch] = $parse($current);
        [$latestMajor, $latestMinor, $latestPatch] = $parse($latest);

        return match (true) {
            $latestMajor !== $currentMajor => 'major',
            $latestMinor !== $currentMinor => 'minor',
            $latestPatch !== $currentPatch => 'patch',
            default => 'none',
        };
    }
}
