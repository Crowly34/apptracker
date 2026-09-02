---
paths:
  - 'app/Mcp/**'
---

# Mcp

## AppTracker MCP server: 7 tools, Markdown files are source of truth
app/Mcp/Servers/AppTrackerServer.php registers 7 tools over the one `applications` table:
list_applications, get_application, create_application, update_status, set_next_action,
attach_document, and refresh_from_vault.

The two hand-maintained Markdown files (config apptracker.moc.*) are the source of truth for
status / tier / applied_at / posting_url. `refresh_from_vault` re-runs `tracker:import` to pull
those in; call it right after editing _Applications.md or _Job Sort Queue.md. The import never
touches next_action / notes / resume_path / cover_letter_path (TrackerImport::ownedAttributes),
so those MCP-written fields survive a re-import. No scheduler yet — refresh is Claude-triggered
via the tool. No delete tool by design.
