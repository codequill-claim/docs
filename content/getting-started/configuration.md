---
title: Configuration
order: 4
---

# Configuration

The CodeQuill CLI uses two types of configuration: **launcher configuration** (project-level settings) and **authentication tokens** (user credentials).

## Launcher Configuration

The CLI launcher reads configuration from JSON files in the following priority order:

1. `$CODEQUILL_CONFIG_DIR/config.json` (if the environment variable is set)
2. `.codequill/config.json` (repository-local, in the current working directory)
3. `~/.codequill/config.json` (global, in the home directory)

### Configuration Options

```json
{
  "codequill_base_url": "https://app.codequill.xyz",
  "codequill_api_base_url": "https://api.codequill.xyz",
  "node_no_deprecation": true,
  "node_tls_reject_unauthorized": 0
}
```

| Key | Type | Description |
|---|---|---|
| `codequill_base_url` | string | Web application URL. Used for display links in CLI output. |
| `codequill_api_base_url` | string | Backend API URL. Set this if you are running a custom instance. |
| `node_no_deprecation` | boolean | Suppress Node.js deprecation warnings. |
| `node_tls_reject_unauthorized` | 0 | Disable TLS certificate verification. **Development only.** |

Most users do not need a launcher configuration file. The defaults connect to the production CodeQuill instance.

## Authentication Tokens

After running `codequill login`, tokens are stored at:

```
~/.config/codequill/config.json
```

This path can be overridden with the `CODEQUILL_CONFIG_DIR` environment variable. The file is created with `0600` permissions (owner read/write only).

```json
{
  "access_token": "eyJhbG...",
  "refresh_token": "eyJhbG...",
  "expires_at": 1700000000,
  "access_expires_at": 1700000000,
  "refresh_expires_at": 1702592000
}
```

Tokens refresh automatically when the access token expires. You should not need to edit this file manually.

## Local Repository Data

When you run `codequill snapshot`, the CLI creates a `.codequill/` directory in your repository root:

```
.codequill/
  config.json              # Optional launcher config overrides
  snapshots/
    index.json             # Snapshot index tracking local and published snapshots
    snapshot-a1b2c3d.json  # Individual snapshot manifests (named by short commit hash)
  proofs/
    proof-abc123.json      # Generated proof-of-inclusion files
```

### Snapshot Index

The `.codequill/snapshots/index.json` file tracks your snapshot history:

```json
{
  "version": "codequill-index:v1",
  "repo_name": "my-org/my-project",
  "updated_at": 1700000000,
  "items": [],
  "latest": {
    "local": null,
    "published": null
  }
}
```

### Adding to `.gitignore`

The `.codequill/` directory contains local state and potentially sensitive data. You may want to add it to your `.gitignore`:

```
.codequill/
```

However, if you want to track snapshot manifests in version control (for auditability), you can selectively include them:

```
# Ignore CodeQuill config and index
.codequill/config.json
.codequill/snapshots/index.json

# But track snapshot manifests
!.codequill/snapshots/snapshot-*.json
```

## Manifest Versions

CodeQuill uses versioned manifest schemas to ensure forward compatibility:

| Schema | Description |
|---|---|
| `codequill-index:v1` | Snapshot index format |
| `codequill-snapshot:v1` | Snapshot manifest format |
| `codequill-proof:v1` | Proof-of-inclusion format |
| `codequill-attestation:v1` | Attestation manifest format |
| `codequill-backup:v1` | Preservation metadata format |
| `codequill-release:v1` | Release manifest format |
| `codequill-envelope:v1` | Encrypted preservation envelope format |
