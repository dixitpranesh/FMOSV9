# CR-001 Laminate Catalog → 3D Rendering Plan

**Input:** Owner laminate library at `C:\laminates`  
**Goal:** Selected laminate(s) drive furniture **3D rendering** (and catalog/swatch UI).  
**Status:** Owner answers recorded (LQ-001/002/003/005) — **no import/code until CR-001 plan approved**  
**Date:** 2026-08-10  
**Decisions:** CRD-008–012

---

## 1. Source inventory

| Item | Value |
| ---- | ----- |
| Path | `C:\laminates` |
| Format | `.webp` only |
| File count | **45** |
| Distinct prefixes | **15** groups |
| Images per group | **3** (`_1`, `_2`, `_3`) |

### Filename pattern (inferred)

```text
{productCode}_{series}_{designIndex}_{variant}.webp

Examples:
  20107_SHR_13_1.webp
  25592_ECO_5_2.webp
  25375_STR_22_3.webp
```

| Token | Examples | Meaning (owner-confirmed) |
| ----- | -------- | ------------------------ |
| `productCode` | 20107, 25592 | Supplier / collection code |
| `series` | `ECO`, `SHR`, `STR` | Texture family — see §1.1 |
| `designIndex` | 13, 5, 22 | Design number within series |
| `variant` | 1, 2, 3 | Colorway index; **each full filename = one SKU** |

### 1.1 Series meanings (CRD-009)

| Code | Name | Texture meaning |
| ---- | ---- | --------------- |
| `ECO` | Echoe | Seamless, rhythmic flute patterns — smooth, flowing, wave-like visual/tactile surface |
| `SHR` | Shore | Balanced, layered grooves with flat outer ridges — structured, defined linear look |
| `STR` | Strand | Clean, continuous straight-line grooves — strict visual symmetry and sharp linear flow |

### Image dimensions (all groups)

| Variant slot | Size | Role for FMOS (proposed) |
| ------------ | ---- | ------------------------ |
| `_1` | 2129×2129 (square) | Primary **albedo / 3D texture** + catalog thumbnail |
| `_2`, `_3` | 2129×948 | Additional **colorways** of same design (also usable as 3D textures) |

Preview PNGs (analysis only): `CR/samples/laminates_preview/` — not production assets.

**Important:** Within one prefix (e.g. `20107_SHR_13`), `_1` / `_2` / `_3` are **different colours**, not photo+swatch of the same colour. Treat each file as a **separate selectable laminate SKU** unless owner says otherwise.

---

## 2. Product behaviour (target)

```text
Catalog Laminate (SKU + texture URL)
        ↓ select on furniture / component / surface role
Furniture finish assignment
        ↓
3D mesh material (Three.js map / color / roughness)
        ↓
Rendered preview + optional export image
```

### Selection levels (required for samples like kids wardrobe)

| Level | Example | Needed? |
| ----- | ------- | ------- |
| Furniture default exterior | Whole wardrobe one laminate | Yes (CRD-010) |
| Furniture default interior | Carcass liner | Yes (CRD-010) |
| Per-component / per-door | Left door / right door different SKUs | **Yes** (CRD-010) |
| Loft vs main | Optional via component override | Yes |

Cutlist “COLOUR” column should resolve from the **same finish assignment** (snapshot at manufacturing release).

---

## 3. Domain model (additive under CR)

Aligns with existing REQ material/catalog concepts; thin MVP first.

### Tables (proposed)

1. **`materials`** (or extend `catalog_products` with category `LAMINATE`)
   - `tenant_id`, `sku`, `name`, `category=LAMINATE`
   - `series_code` (`ECO`/`SHR`/`STR`), `series_name` (`Echoe`/`Shore`/`Strand`)
   - `supplier_code` (e.g. `20107`)
   - `design_index`, `colorway_index`
   - `name` = SKU code (CRD-011), e.g. `20107_SHR_13_1`
   - `default_roughness`, `default_metalness` (for 3D)
   - `status`, pricing fields later

2. **`material_assets`**
   - `material_id`
   - `asset_type`: `TEXTURE_ALBEDO` | `THUMBNAIL` | `SWATCH` | `GALLERY`
   - `storage_path` / `public_url`
   - `mime`, `width`, `height`
   - `is_primary`

3. **Furniture / component finish FKs** (CR Phase 3–4)
   - `furniture_instances.exterior_finish_id`, `interior_finish_id` (nullable)
   - `furniture_components.finish_id` (overrides furniture default)

### Storage layout (app-owned copy — do not read `C:\laminates` at runtime)

```text
storage/tenants/{tenant_id}/materials/{sku}/
  albedo.webp          # or .jpg derived
  thumb.webp
public/media/...       # authenticated or signed URL for Three.js
```

Import copies from `C:\laminates` once via CLI/seed; originals stay outside the repo.

---

## 4. Import plan (implementation phase, after approval)

**Tool:** `bin/import_laminates.php --path=C:\laminates --tenant=demo`

Steps:

1. Scan `*.webp`
2. Parse filename → sku = full basename without extension  
   Example SKU: `20107_SHR_13_1`
3. Upsert catalog/material row (idempotent on `tenant_id+sku`)
4. Copy file into tenant storage; register `TEXTURE_ALBEDO` + generate thumb
5. Optional: average-colour sample hex for swatch chip when texture fails to load
6. Publish as `ACTIVE` for demo tenant

**Git policy:** Do **not** commit the full 45 webp set into the repo. Import from local path / seed pack on each environment.

---

## 5. 3D rendering integration (Phase 6 enhancement)

Current MVP 3D uses flat `MeshStandardMaterial` colours. Target:

1. `GET /furniture/{id}/3d-model` includes per-mesh:
   - `finish_id`, `texture_url`, `roughness`, `metalness`, `uv_scale`, `grain_rotation`
2. Frontend Three.js:
   - `TextureLoader.load(texture_url)`
   - `map`, `wrapS/T = RepeatWrapping`, repeat from panel mm → texture scale
   - Fallback: solid `color` hex if texture missing
3. Re-render when user changes laminate in Specification / Components UI
4. Export canvas still captures textured result

Grain/orientation for fluted laminates must respect panel grain rules later (nesting + viz).

---

## 6. UI touchpoints

| Place | Behaviour |
| ----- | --------- |
| Catalog / Materials | Browse laminates by series; show thumb + code |
| Furniture Specification | Pick exterior / interior laminate |
| Component editor | Override finish per door/shutter/exposed panel |
| Furniture 3D tab | Live texture update |
| Cutlist | Colour = laminate name/SKU |

---

## 7. Impact on CR-001 phases

| Phase | Update |
| ----- | ------ |
| **1** | Include `materials` / `material_assets` (or catalog extension) in schema |
| **3** | Spec UI: exterior/interior finish pickers backed by laminate catalog |
| **4** | `furniture_components.finish_id` |
| **6** | **Must** apply selected laminate textures in Three.js (not flat colour only) |
| **8–10** | Panel/cutlist colour from finish snapshot |
| **15** | AC: change laminate → 3D updates; cutlist colour matches |

Reference REQ: Material & Catalog §32 (laminate texture), §151 (catalog-to-3D).

---

## 8. Owner answers (recorded)

| ID | Decision |
| -- | -------- |
| **LQ-001** | Each file = one SKU → **CRD-008** |
| **LQ-002** | ECO=Echoe, SHR=Shore, STR=Strand (meanings in §1.1) → **CRD-009** |
| **LQ-003** | Multi-finish (exterior + interior + per-component) → **CRD-010** |
| **LQ-005** | Display name = same code/SKU → **CRD-011** |
| **LQ-004** | Still default **A** (import CLI from `C:\laminates`) unless owner later provides zip/CSV |

---

## 9. Non-goals (this CR slice)

- Full PBR pack (normal/roughness maps) — albedo + roughness scalar first
- Photoreal room HDRI matching sample marketing renders
- Committing binary laminate library into git

---

## 10. Status

Laminate LQ decisions recorded (CRD-008–012).  
Awaiting **`APPROVE CR-001 PLAN`** before coding/import.
