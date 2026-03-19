---
title: Authentication
description: "CLI authentication commands: login with approval phrase flow, check identity with who, and view subscription quotas with quota."
order: 1
---

# Authentication

These commands manage CLI authentication and display information about the current session and workspace.

---

## codequill login

Authenticate the CLI using the approval phrase flow. This initiates a device-code authentication exchange between the CLI and the CodeQuill web application.

### Syntax

```bash
codequill login
```

### Options

This command has no options.

### How It Works

1. The CLI sends a `POST` request to `/v1/cli/auth/init`, which returns a login URL and a unique approval phrase.
2. The CLI displays the URL and the approval phrase in your terminal.
3. You open the URL in a browser where you are already signed in to the CodeQuill web application.
4. You verify that the approval phrase shown in the browser matches the one displayed by the CLI.
5. You approve the login request in the browser.
6. Meanwhile, the CLI polls `/v1/cli/auth/token` approximately every 5 seconds, waiting for the approval.
7. Once approved, the CLI receives an access token and a refresh token, which it saves locally.
8. If no approval is received within **3 minutes**, the login attempt times out.

Tokens are stored at `~/.config/codequill/config.json` and refresh automatically during subsequent CLI operations.

### Example

```bash
codequill login
```

```
Login URL: https://app.codequill.xyz/cli/auth?code=abc123
Approval phrase: amber-castle-fox

Waiting for approval...

Authenticated successfully.
```

### Notes

- You must be signed in to the CodeQuill web application before opening the login URL.
- The approval phrase is a human-readable phrase designed to prevent phishing. Always confirm that the phrase in your browser matches the phrase in your terminal before approving.
- If the 3-minute timeout elapses, run `codequill login` again to start a new session.

---

## codequill who

Display the currently authenticated user. This is useful for verifying which account the CLI is operating under, especially when working across multiple workspaces or machines.

### Syntax

```bash
codequill who
```

### Options

This command has no options.

### Example

```bash
codequill who
```

```
Logged in as: dtucker
Workspace: my-org
```

### Notes

- If no user is authenticated, the command will indicate that no session is active.
- This command contacts the CodeQuill backend to resolve the current token into user and workspace information.

---

## codequill quota

Display the subscription plan and quota usage for the current workspace. This includes limits and current consumption for snapshots, attestations, preservations, and other metered resources.

### Syntax

```bash
codequill quota [options]
```

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--json` | boolean | `false` | Output machine-readable JSON instead of the default human-readable table |

### Example

```bash
codequill quota
```

```
Workspace: my-org
Plan: Pro

Snapshots:    42 / 500
Attestations: 12 / 100
Preservations: 3 / 50
```

#### JSON output

```bash
codequill quota --json
```

```json
{
  "workspace": "my-org",
  "plan": "pro",
  "usage": {
    "snapshots": { "used": 42, "limit": 500 },
    "attestations": { "used": 12, "limit": 100 },
    "preservations": { "used": 3, "limit": 50 }
  }
}
```

### Notes

- This command must be run inside a git repository with a GitHub remote origin. The CLI uses the remote to resolve the workspace.
- The `--json` flag is useful for scripting and CI pipelines where you need to programmatically check whether quota is available before performing an operation.
