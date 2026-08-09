# ADR-001 Modular Monolith

FMOS ships as a modular PHP monolith with domain folders under `src/Domains/*`.
Engines may be extracted later; domain boundaries must remain clean.
