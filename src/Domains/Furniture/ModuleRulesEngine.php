<?php

declare(strict_types=1);

namespace Fmos\Domains\Furniture;

/**
 * Deterministic eligibility / recommendation / validation / apply for internal configs.
 * Mutates layout JSON only — FurnitureLayoutEngine remains the manufacturing generator.
 */
final class ModuleRulesEngine
{
    /**
     * @param array{width?:float|int,height?:float|int,depth?:float|int} $dims
     * @param array<string,mixed>|null $layout
     * @return array{
     *   module_type:string,
     *   recommended:list<array<string,mixed>>,
     *   optional:list<array<string,mixed>>,
     *   required:list<array<string,mixed>>,
     *   unavailable:list<array<string,mixed>>,
     *   present_config_ids:list<string>,
     *   validation:array{ok:bool,issues:list<array<string,mixed>>}
     * }
     */
    public function recommend(string $moduleType, array $dims, ?array $layout = null): array
    {
        $module = ModuleTypeCatalog::get($moduleType);
        if ($module === null) {
            throw new \InvalidArgumentException("Unknown module type: {$moduleType}");
        }

        $layout = $layout ?? ['bays' => []];
        $present = $this->detectPresentConfigIds($layout);
        $w = (float) ($dims['width'] ?? 0);
        $h = (float) ($dims['height'] ?? 0);
        $d = (float) ($dims['depth'] ?? 0);

        $recommended = [];
        $optional = [];
        $required = [];
        $unavailable = [];

        $allowed = $module['allowed_config_ids'] ?? [];
        foreach ($allowed as $cfgId) {
            $cfg = InternalConfigCatalog::get($cfgId);
            if ($cfg === null) {
                continue;
            }
            $entry = $this->enrichConfigEntry($cfg, $module, $w, $h, $d, $present, $layout);
            if ($entry['status'] === 'unavailable') {
                $unavailable[] = $entry;
                continue;
            }
            $tier = $this->resolveTier($cfgId, $module, $cfg);
            $entry['tier'] = $tier;
            if ($tier === 'required') {
                $required[] = $entry;
            } elseif ($tier === 'recommended') {
                $recommended[] = $entry;
            } else {
                $optional[] = $entry;
            }
        }

        return [
            'module_type' => $moduleType,
            'recommended' => $recommended,
            'optional' => $optional,
            'required' => $required,
            'unavailable' => $unavailable,
            'present_config_ids' => $present,
            'validation' => $this->validate($moduleType, $dims, $layout),
        ];
    }

    /**
     * @param array{width?:float|int,height?:float|int,depth?:float|int} $dims
     * @param array<string,mixed> $layout
     * @return array{ok:bool,issues:list<array{code:string,message:string,config_id?:string,bay_id?:string}>}
     */
    public function validate(string $moduleType, array $dims, array $layout): array
    {
        $issues = [];
        $module = ModuleTypeCatalog::get($moduleType);
        if ($module === null) {
            return [
                'ok' => false,
                'issues' => [['code' => 'UNKNOWN_MODULE', 'message' => "Unknown module type: {$moduleType}"]],
            ];
        }

        $w = (float) ($dims['width'] ?? 0);
        $h = (float) ($dims['height'] ?? 0);
        $d = (float) ($dims['depth'] ?? 0);
        $present = $this->detectPresentConfigIds($layout);

        foreach ($present as $cfgId) {
            $cfg = InternalConfigCatalog::get($cfgId);
            if ($cfg === null) {
                continue;
            }
            $elig = $this->eligibilityFailure($cfg, $w, $h, $d);
            if ($elig !== null) {
                $issues[] = [
                    'code' => 'DIMENSION',
                    'message' => $elig,
                    'config_id' => $cfgId,
                ];
            }
            foreach ($cfg['incompatible_with'] ?? [] as $other) {
                if (in_array($other, $present, true)) {
                    $issues[] = [
                        'code' => 'INCOMPATIBLE',
                        'message' => "{$cfgId} is incompatible with {$other} in the same module.",
                        'config_id' => $cfgId,
                    ];
                }
            }
        }

        // Sink / plumbing niche should not share a bay with full shelf packs (advisory rule).
        foreach ($layout['bays'] ?? [] as $bay) {
            $types = [];
            foreach ($bay['sections'] ?? [] as $sec) {
                $types[] = strtoupper((string) ($sec['type'] ?? ''));
            }
            if (in_array('OPEN', $types, true) && in_array('SHELVES', $types, true)) {
                $hasPlumbing = false;
                foreach ($bay['sections'] ?? [] as $sec) {
                    $label = strtolower((string) ($sec['label'] ?? ''));
                    if (($sec['type'] ?? '') === 'OPEN' && (str_contains($label, 'plumb') || str_contains($label, 'sink'))) {
                        $hasPlumbing = true;
                    }
                }
                if ($hasPlumbing) {
                    $issues[] = [
                        'code' => 'PLUMBING_CLEARANCE',
                        'message' => 'Plumbing/sink niche should not share a bay with shelves (clearance).',
                        'bay_id' => (string) ($bay['id'] ?? ''),
                    ];
                }
            }
        }

        return ['ok' => $issues === [], 'issues' => $issues];
    }

    /**
     * Apply a config to a bay (append section or replace bay when replace_bay).
     *
     * @param array<string,mixed> $layout
     * @return array{layout:array<string,mixed>,door_type:?string,shutter_count:?int}
     */
    public function apply(string $configId, array $layout, ?string $bayId = null): array
    {
        $cfg = InternalConfigCatalog::get($configId);
        if ($cfg === null) {
            throw new \InvalidArgumentException("Unknown internal config: {$configId}");
        }
        if (empty($cfg['implemented'])) {
            throw new \InvalidArgumentException(
                (string) ($cfg['unavailable_reason'] ?? "Config {$configId} is not implemented yet.")
            );
        }
        if (empty($cfg['maps_to_section_type']) || empty($cfg['section_defaults'])) {
            throw new \InvalidArgumentException("Config {$configId} has no section mapping.");
        }

        if (empty($layout['bays']) || !is_array($layout['bays'])) {
            $layout['bays'] = [[
                'id' => 'bay-1',
                'label' => 'Bay 1',
                'width_mm' => null,
                'sections' => [],
            ]];
        }

        $bi = 0;
        if ($bayId !== null && $bayId !== '') {
            foreach ($layout['bays'] as $i => $bay) {
                if ((string) ($bay['id'] ?? '') === $bayId) {
                    $bi = (int) $i;
                    break;
                }
            }
        }

        $section = $cfg['section_defaults'];
        if (!empty($cfg['replace_bay'])) {
            $layout['bays'][$bi]['sections'] = [$section];
            if (isset($cfg['section_defaults']['label'])) {
                $layout['bays'][$bi]['label'] = $cfg['section_defaults']['label'];
            }
        } else {
            // Avoid duplicate same type when already present in bay (toggle-friendly).
            $existingTypes = array_map(
                static fn ($s) => strtoupper((string) ($s['type'] ?? '')),
                $layout['bays'][$bi]['sections'] ?? []
            );
            $type = strtoupper((string) $section['type']);
            if (!in_array($type, $existingTypes, true)) {
                $layout['bays'][$bi]['sections'][] = $section;
            }
        }

        $doorType = null;
        $shutterCount = null;
        foreach ($cfg['layout_overrides'] ?? [] as $k => $v) {
            $layout[$k] = $v;
            if ($k === 'door_type') {
                $doorType = (string) $v;
                if ($v === 'NONE') {
                    $shutterCount = 0;
                }
            }
        }

        return [
            'layout' => $layout,
            'door_type' => $doorType,
            'shutter_count' => $shutterCount,
        ];
    }

    /**
     * Remove sections matching a config's section type from a bay (or all bays).
     *
     * @param array<string,mixed> $layout
     * @return array<string,mixed>
     */
    public function remove(string $configId, array $layout, ?string $bayId = null): array
    {
        $cfg = InternalConfigCatalog::get($configId);
        if ($cfg === null) {
            throw new \InvalidArgumentException("Unknown internal config: {$configId}");
        }
        $type = strtoupper((string) ($cfg['maps_to_section_type'] ?? ''));
        if ($type === '') {
            return $layout;
        }

        foreach ($layout['bays'] ?? [] as $i => $bay) {
            if ($bayId !== null && $bayId !== '' && (string) ($bay['id'] ?? '') !== $bayId) {
                continue;
            }
            $layout['bays'][$i]['sections'] = array_values(array_filter(
                $bay['sections'] ?? [],
                static fn ($s) => strtoupper((string) ($s['type'] ?? '')) !== $type
            ));
            if ($layout['bays'][$i]['sections'] === []) {
                $layout['bays'][$i]['sections'] = [[
                    'type' => 'OPEN',
                    'height_mm' => null,
                    'label' => 'Open',
                ]];
            }
        }
        return $layout;
    }

    /**
     * Infer which catalog configs are present from section types / labels.
     *
     * @param array<string,mixed> $layout
     * @return list<string>
     */
    public function detectPresentConfigIds(array $layout): array
    {
        $found = [];
        $sectionTypes = [];
        foreach ($layout['bays'] ?? [] as $bay) {
            foreach ($bay['sections'] ?? [] as $sec) {
                $type = strtoupper((string) ($sec['type'] ?? ''));
                $sectionTypes[$type] = true;
                $label = strtolower((string) ($sec['label'] ?? ''));
                if ($type === 'OPEN' && (str_contains($label, 'plumb') || str_contains($label, 'sink'))) {
                    $found['CFG_KB_SINK'] = true;
                }
            }
        }
        $door = strtoupper((string) ($layout['door_type'] ?? ''));
        if (isset($sectionTypes['HANGING'])) {
            $found['CFG_HANGING'] = true;
        }
        if (isset($sectionTypes['SHELVES'])) {
            $found['CFG_SHELVES'] = true;
            $found['CFG_KB_SHELF'] = true;
        }
        if (isset($sectionTypes['DRAWERS'])) {
            $found['CFG_DRAWERS'] = true;
            if ($door === 'NONE') {
                $found['CFG_KB_DRAWERS'] = true;
            }
        }
        if (isset($sectionTypes['OPEN'])) {
            $found['CFG_OPEN'] = true;
        }
        if (isset($sectionTypes['MIRROR'])) {
            $found['CFG_MIRROR'] = true;
        }
        return array_keys($found);
    }

    /**
     * @param array<string,mixed> $cfg
     * @param array<string,mixed> $module
     * @param list<string> $present
     * @param array<string,mixed> $layout
     * @return array<string,mixed>
     */
    private function enrichConfigEntry(
        array $cfg,
        array $module,
        float $w,
        float $h,
        float $d,
        array $present,
        array $layout
    ): array {
        $id = (string) $cfg['id'];
        $reasons = [];
        $status = 'available';

        if (empty($cfg['implemented'])) {
            $status = 'unavailable';
            $reasons[] = (string) ($cfg['unavailable_reason'] ?? 'Not implemented yet.');
        }

        $elig = $this->eligibilityFailure($cfg, $w, $h, $d);
        if ($elig !== null) {
            $status = 'unavailable';
            $reasons[] = $elig;
        }

        foreach ($cfg['incompatible_with'] ?? [] as $other) {
            if (in_array($other, $present, true)) {
                $status = 'unavailable';
                $reasons[] = "Incompatible with {$other} already in layout.";
            }
        }

        return [
            'id' => $id,
            'name' => $cfg['name'],
            'description' => $cfg['description'] ?? '',
            'category' => $cfg['category'] ?? '',
            'maps_to_section_type' => $cfg['maps_to_section_type'],
            'implemented' => !empty($cfg['implemented']),
            'present' => in_array($id, $present, true)
                || (
                    !empty($cfg['maps_to_section_type'])
                    && in_array(
                        $this->sectionTypeConfigAlias((string) $cfg['maps_to_section_type']),
                        $present,
                        true
                    )
                    && $this->sectionTypePresent($layout, (string) $cfg['maps_to_section_type'])
                ),
            'status' => $status,
            'reasons' => $reasons,
            'eligibility' => $cfg['eligibility'] ?? [],
            'tier' => $cfg['tier'] ?? 'optional',
        ];
    }

    private function sectionTypeConfigAlias(string $sectionType): string
    {
        return match (strtoupper($sectionType)) {
            'HANGING' => 'CFG_HANGING',
            'SHELVES' => 'CFG_SHELVES',
            'DRAWERS' => 'CFG_DRAWERS',
            'OPEN' => 'CFG_OPEN',
            'MIRROR' => 'CFG_MIRROR',
            default => '',
        };
    }

    /** @param array<string,mixed> $layout */
    private function sectionTypePresent(array $layout, string $type): bool
    {
        $t = strtoupper($type);
        foreach ($layout['bays'] ?? [] as $bay) {
            foreach ($bay['sections'] ?? [] as $sec) {
                if (strtoupper((string) ($sec['type'] ?? '')) === $t) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array<string,mixed> $cfg
     * @param array<string,mixed> $module
     */
    private function resolveTier(string $cfgId, array $module, array $cfg): string
    {
        if (in_array($cfgId, $module['required_config_ids'] ?? [], true)) {
            return 'required';
        }
        if (in_array($cfgId, $module['recommended_config_ids'] ?? [], true)) {
            return 'recommended';
        }
        if (in_array($cfgId, $module['optional_config_ids'] ?? [], true)) {
            return 'optional';
        }
        return (string) ($cfg['tier'] ?? 'optional');
    }

    /** @param array<string,mixed> $cfg */
    private function eligibilityFailure(array $cfg, float $w, float $h, float $d): ?string
    {
        $e = $cfg['eligibility'] ?? [];
        if ($w > 0 && isset($e['min_width_mm']) && $w < (float) $e['min_width_mm']) {
            return sprintf(
                'Requires width ≥ %s mm (module is %s mm). [%s]',
                (int) $e['min_width_mm'],
                (int) $w,
                $e['rule_source'] ?? 'rule'
            );
        }
        if ($w > 0 && isset($e['max_width_mm']) && $w > (float) $e['max_width_mm']) {
            return sprintf(
                'Requires width ≤ %s mm (module is %s mm). [%s]',
                (int) $e['max_width_mm'],
                (int) $w,
                $e['rule_source'] ?? 'rule'
            );
        }
        if ($h > 0 && isset($e['min_height_mm']) && $h < (float) $e['min_height_mm']) {
            return sprintf(
                'Requires height ≥ %s mm (module is %s mm). [%s]',
                (int) $e['min_height_mm'],
                (int) $h,
                $e['rule_source'] ?? 'rule'
            );
        }
        if ($d > 0 && isset($e['min_depth_mm']) && $d < (float) $e['min_depth_mm']) {
            return sprintf(
                'Requires depth ≥ %s mm (module is %s mm). [%s]',
                (int) $e['min_depth_mm'],
                (int) $d,
                $e['rule_source'] ?? 'rule'
            );
        }
        return null;
    }
}
