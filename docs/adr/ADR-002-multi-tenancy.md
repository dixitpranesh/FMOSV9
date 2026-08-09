# ADR-002 Multi-Tenancy

Isolation root is `tenant_id`. Organization is a real child tier (DEC-004).
All tenant-scoped queries MUST filter by tenant server-side.
