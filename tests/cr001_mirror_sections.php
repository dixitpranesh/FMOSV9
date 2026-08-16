<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Fmos\Domains\Furniture\FurnitureExpo;
use Fmos\Domains\Furniture\FurnitureLayoutEngine;
use Fmos\Domains\Furniture\FurnitureMirror;
use Fmos\Domains\Furniture\PanelFinishResolver;

function ok(string $msg): void
{
    echo "  OK  $msg\n";
}

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('ASSERT: ' . $msg);
    }
    ok($msg);
}

echo "Mirror / EXPO dressing-section tests\n";

// Mirror ≠ EXPO
assertTrue(FurnitureExpo::isExpo('MIRROR_PANEL', FurnitureExpo::normalize(null)) === false, 'mirror defaults NON-EXPO');
assertTrue(FurnitureExpo::isExpo('NICHE_BACK', FurnitureExpo::normalize(null)) === true, 'niche back defaults EXPO');
assertTrue(FurnitureExpo::isExpo('NICHE_SIDE_LEFT', FurnitureExpo::normalize(null)) === true, 'niche left defaults EXPO');
assertTrue(FurnitureExpo::isExpo('NICHE_SIDE_RIGHT', FurnitureExpo::normalize(null)) === true, 'niche right defaults EXPO');
assertTrue(FurnitureExpo::isExpo('DRAWER_FRONT', FurnitureExpo::normalize(null)) === true, 'drawers still default EXPO');
assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Right - Mirror - Mirror') === 'MIRROR_PANEL', 'infer mirror role');
assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Right - Mirror - Niche Back') === 'NICHE_BACK', 'infer niche back');
assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Right - Mirror - Niche Side Left') === 'NICHE_SIDE_LEFT', 'infer niche left');
assertTrue(FurnitureExpo::inferRoleFromName('Wardrobe - Right - Mirror - Niche Side Right') === 'NICHE_SIDE_RIGHT', 'infer niche right');
assertTrue(PanelFinishResolver::expoFaceIndex('NICHE_SIDE_LEFT') === 0, 'left niche face +X');
assertTrue(PanelFinishResolver::expoFaceIndex('NICHE_SIDE_RIGHT') === 1, 'right niche face -X');

$faces = PanelFinishResolver::resolve('MIRROR_PANEL', FurnitureExpo::normalize(null), 10, 20);
assertTrue($faces['expo'] === false, 'resolver forces mirror non-EXPO');
assertTrue(($faces['face_exterior']['finish_role'] ?? '') === 'mirror', 'mirror finish role');

$partial = FurnitureMirror::resolveGlass(['mirror_margin_mm' => 80], 800, 1200);
assertTrue($partial['width_mm'] === 640.0, 'partial mirror width = bay − 2×margin');
assertTrue($partial['height_mm'] === 1040.0, 'partial mirror height = sec − 2×margin');
assertTrue($partial['full_section'] === false, 'default margin is not full-section');

$full = FurnitureMirror::resolveGlass(['mirror_margin_mm' => 0], 800, 1200);
assertTrue($full['full_section'] === true, 'margin 0 = full-section glass by configuration');

$explicit = FurnitureMirror::resolveGlass(['mirror_width_mm' => 500, 'mirror_height_mm' => 900], 800, 1200);
assertTrue($explicit['width_mm'] === 500.0 && $explicit['height_mm'] === 900.0, 'explicit glass size honored');

$engine = new FurnitureLayoutEngine();
$base = [
    'width' => 2400,
    'height' => 2400,
    'depth' => 600,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 2,
    'door_type' => 'HINGED',
    'layout' => [
        'plinth_height_mm' => 110,
        'partition_thickness_mm' => 18,
        'door_type' => 'HINGED',
        'loft' => ['enabled' => false],
        'bays' => [[
            'id' => 'bay-1',
            'label' => 'Right',
            'width_mm' => null,
            'sections' => [
                ['type' => 'MIRROR', 'height_mm' => 1400, 'label' => 'Mirror', 'mirror_margin_mm' => 80],
                ['type' => 'DRAWERS', 'height_mm' => 600, 'drawer_count' => 3, 'drawer_height_mm' => 180, 'label' => 'Drawers'],
            ],
        ]],
    ],
];

$parts = $engine->generate('WARDROBE', $base);
$roles = array_column($parts, 'role');
assertTrue(in_array('MIRROR_PANEL', $roles, true), 'mirror panel generated');
assertTrue(in_array('NICHE_BACK', $roles, true), 'niche back generated');
assertTrue(in_array('NICHE_SIDE_LEFT', $roles, true), 'niche left side generated');
assertTrue(in_array('NICHE_SIDE_RIGHT', $roles, true), 'niche right side generated');
assertTrue(in_array('NICHE_SILL', $roles, true), 'niche sill generated');
assertTrue(in_array('NICHE_HEADER', $roles, true), 'niche header generated');
assertTrue(in_array('DRAWER_FRONT', $roles, true), 'drawer fronts remain separate');

$mirrors = array_values(array_filter($parts, static fn ($c) => ($c['role'] ?? '') === 'MIRROR_PANEL'));
$niches = array_values(array_filter($parts, static fn ($c) => ($c['role'] ?? '') === 'NICHE_BACK'));
assertTrue(count($mirrors) === 1 && count($niches) === 1, 'one glass + one niche back');
assertTrue((float) $mirrors[0]['thickness_mm'] === 5.0, 'glass thickness 5mm');
assertTrue((float) $mirrors[0]['width_mm'] < (float) $niches[0]['width_mm'], 'glass narrower than niche surround');
assertTrue((float) $mirrors[0]['length_mm'] < (float) $niches[0]['length_mm'], 'glass shorter than niche surround');

// Stamp expo like FurnitureEngine::generateComponents
$expoMap = FurnitureExpo::normalize(null);
foreach ($parts as &$c) {
    $role = (string) ($c['role'] ?? '');
    if ($role === '') {
        continue;
    }
    $c['expo'] = PanelFinishResolver::resolve($role, $expoMap, 1, 2)['expo'];
}
unset($c);
$byRole = [];
foreach ($parts as $c) {
    $byRole[$c['role']] = $c;
}
assertTrue(($byRole['MIRROR_PANEL']['expo'] ?? true) === false, 'stamped mirror non-EXPO');
assertTrue(($byRole['NICHE_BACK']['expo'] ?? false) === true, 'stamped niche back EXPO');
assertTrue(($byRole['NICHE_SIDE_LEFT']['expo'] ?? false) === true, 'stamped niche left EXPO');
assertTrue(($byRole['NICHE_SIDE_RIGHT']['expo'] ?? false) === true, 'stamped niche right EXPO');
assertTrue(($byRole['NICHE_SILL']['expo'] ?? false) === true, 'stamped niche sill EXPO');
assertTrue(($byRole['NICHE_HEADER']['expo'] ?? false) === true, 'stamped niche header EXPO');
assertTrue(($byRole['DRAWER_FRONT']['expo'] ?? false) === true, 'stamped drawer EXPO');

$openOnly = $engine->generate('WARDROBE', [
    'width' => 1200,
    'height' => 2100,
    'depth' => 500,
    'carcass_thickness' => 18,
    'back_thickness' => 18,
    'shutter_count' => 1,
    'door_type' => 'HINGED',
    'layout' => [
        'plinth_height_mm' => 0,
        'door_type' => 'HINGED',
        'bays' => [[
            'id' => 'bay-1',
            'label' => 'Main',
            'sections' => [
                ['type' => 'OPEN', 'height_mm' => null, 'label' => 'Open'],
            ],
        ]],
    ],
]);
$openRoles = array_column($openOnly, 'role');
assertTrue(!in_array('MIRROR_PANEL', $openRoles, true), 'OPEN does not create mirror');
assertTrue(in_array('NICHE_BACK', $openRoles, true), 'OPEN creates niche back (visible)');
assertTrue(in_array('NICHE_SIDE_LEFT', $openRoles, true), 'OPEN creates niche sides');

echo "Mirror / EXPO dressing-section tests done\n";
