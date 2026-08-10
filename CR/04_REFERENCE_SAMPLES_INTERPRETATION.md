# CR-001 Reference Samples Interpretation

**Purpose:** Capture output expectations from owner-provided samples.  
**Rule:** Samples are **illustrative only** and **not from one shared project**. Do **not** hard-code names, dimensions, colours, or layouts from these files.

**Date:** 2026-08-10

---

## 1. Files inventoried

| File (in `CR/samples/`) | Type | What it demonstrates |
| ----------------------- | ---- | -------------------- |
| `2d_kids_bedroom_sliding_wardrobe_elevation.png` | 2D front elevation | Internal bays, loft, drawers, shelves, exposed panel, mm dimensions |
| `2d_bedroom01_sliding_wardrobe_elevation.png` | 2D front elevation | Wider wall-to-wall wardrobe, loft divisions, skirting, complex columns |
| `3d_bedroom01_sliding_wardrobe.png` | 3D presentation | Built-in sliding wardrobe + loft, bridge over doorway, gloss finish |
| `3d_kids_bedroom_sliding_wardrobe.png` | 3D presentation | Colour-blocked sliding doors + loft doors in room context |
| `cutlist_tv_unit.png` | Cutlist spreadsheet | Finishing vs cutting sizes, edge columns, thickness, colour, notes |
| `sheet_layout_2440x1220.png` | Nesting sheet layout | Visual panel placement on sheet with margin/gap and labels |

Original chat attachment names (for traceability):

- `another_sample_2d-…png`
- `Sample_2d-…png`
- `Sample_Cutlist-…png`
- `Sheet_Layout_Sample_-…png`
- `Sample_3d-…png`
- `Another_Sample_3d_-…png`

---

## 2. What “good” 2D furniture output looks like

From the elevation samples, FMOS furniture 2D must support:

### Drawing type
- **Front elevation of internal carcass layout** (not only an outer box)
- Optional later: plan, sides, sections (already in CR)

### Structural language visible in samples
- **Plinth / skirting** band (~110 mm in samples — value must be configurable, not hard-coded)
- **Main body** height band
- **Loft** band above main body
- **Vertical partitions / exposed panels**
- **Internal zones:** hanging, shelf stacks, chest drawers, narrow utility columns
- **Wall adjacency / offsets** (e.g. small gap to wall)

### Dimensioning behaviour
- Overall outer dimensions
- Bay / compartment clear sizes
- Component clear heights (drawer, shelf pitch)
- Partition thickness callouts
- Dimensions driven by geometry — change width → redraw dimensions

### Annotation
- Zone labels (`LOFT`, `SHELF`, `CHEST DRAWER`, etc.)
- Callouts (`EXPOSED PANEL`)
- Title text (furniture name) — from furniture record, not sample string

### Implication for domain model
Templates like “simple 5-panel wardrobe” are **insufficient** for CR acceptance.  
Need a **bay/zone component tree** capable of loft + plinth + multi-column internals + sliding doors as separate design elements.

---

## 3. What “good” 3D furniture output looks like

From the 3D samples:

- Photoreal / presentation-quality **assembled furniture in context** is the aspiration
- MVP engineering 3D can be simpler, but must still show:
  - Correct overall proportions (W×H×D, loft, plinth)
  - Distinct door/shutter faces (incl. multi-colour)
  - Loft volumes
  - Optional room context later (floor design optional)
- Export target for first release: at least **rendered image**; GLB optional stretch

**Do not** treat sample room décor (curtains, bed, art) as product scope.

---

## 4. Cutlist rules inferred from sample (must be configurable)

Observed TV UNIT cutlist pattern:

| Concept | Sample observation | Implementation rule |
| ------- | ------------------ | ------------------- |
| Finishing size | Final part size (mm) | Store as finishing_length / finishing_width |
| Cutting size | Smaller than finishing when edged | Store separately; compute via rules |
| Reduction | Often **−2 mm on both L and W** when 0.8 edge on opposite sides | Likely `cutting = finishing − (edgeA + edgeB)` per axis, with rounding policy |
| Edges 1–4 | Numeric thickness (0.8, 1.3) or blank | Per-edge required + thickness + colour |
| No edge on 6mm backs | Edge columns empty | Cutting may equal finishing (or tenant rule) |
| Thickness | 18MM carcass, 6MM back/bottom | From material / component |
| Colour / edgeband colour | Text codes | From catalog/finish records |
| NOTE | e.g. `GROOVE` | Machining flags on component/panel |

### Proposed default tenant rule (seeded, editable — CRD-005)

```text
For each edged axis:
  cutting_dim = finishing_dim - sum(edge_thickness on the two opposite sides)
If no edges on that axis:
  cutting_dim = finishing_dim
Rounding: round to 1 decimal mm (configurable)
```

**Decision still needed from owner:** confirm whether sample’s consistent −2 mm is exactly `0.8+0.8` with rounding to integer, or a fixed “1 mm per edged side” shop rule.

---

## 5. Sheet layout / nesting expectations

From sheet sample:

- Sheet size example **2440 × 1220** appears as a **sheet definition**, not an engine constant
- Outer border + **inner margin** (usable area)
- Panels drawn as rectangles with:
  - part name
  - L × W × thickness
- Visible **gap/kerf** between parts
- Significant **waste/offcut** region is OK and should be measurable
- Labels are manufacturing-oriented (“shelf”, “plinth board”), traceable to furniture/component

### Nesting UI acceptance bar
Not only JSON coordinates — a **graphical sheet** like the sample (canvas/SVG/PDF).

---

## 6. Cross-sample consistency note

These files are **not one project**:

| Sample set | Furniture shown |
| ---------- | --------------- |
| 2D/3D kids | Kids sliding wardrobe |
| 2D/3D bedroom 01 | Bedroom sliding wardrobe (different width/layout) |
| Cutlist | TV Unit |
| Sheet layout | Mixed shelves/plinth (unnamed project) |

Therefore implementation must generate outputs from **live furniture data**, using these only as **quality/format references**.

---

## 7. Impact on CR-001 plan (updates)

| Plan area | Update from samples |
| --------- | ------------------- |
| Phase 3–4 Spec/Components | Must support loft, plinth, multi-bay, drawers, exposed panels, sliding doors |
| Phase 5 2D | Front elevation with internal clear dims + labels is the primary reference style |
| Phase 6 3D | Presentation-like shutter/loft differentiation; room context optional |
| Phase 8 Panels | Finishing vs cutting + 4 edge thicknesses + notes/groove |
| Phase 10 Cutlist | Match column semantics of sample spreadsheet |
| Phase 11 Nesting | Visual sheet with margin, gap, labels, waste metrics |
| Seed sheet | May seed 2440×1220 as a **default sheet row**, never hard-code in solver |

---

## 8. Questions for owner (from samples only)

### RQ-001 — Cutting size formula
For edged panels in your shop, is cutting size:

- **A)** `finishing − (edge_thickness_side1 + edge_thickness_side2)` per axis (matches 0.8+0.8→~2mm)
- **B)** Fixed 1 mm per edged side regardless of band thickness
- **C)** Other (please state)

### RQ-002 — Edge column meaning (1,2,3,4)
Map to panel sides as:

- **A)** 1=Length-Left, 2=Length-Right, 3=Width-Front, 4=Width-Back (or Top/Bottom/Left/Right in grain frame)
- **B)** You will provide the exact shop convention

### RQ-003 — 2D first view priority for MVP of furniture workspace
- **A)** Front elevation (internal) first — matches samples
- **B)** Plan + front together from day one

---

## 9. Status

Samples understood and filed under `CR/samples/`.  
Still waiting for **`APPROVE CR-001 PLAN`** (plus RQ-001–003 answers preferred) before coding.
