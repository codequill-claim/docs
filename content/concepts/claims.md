---
title: Claims
order: 2
---

# Claims

A claim is an on-chain evidence record that links a GitHub repository to a workspace authority. It establishes who is authorized to speak for a repository within the CodeQuill system.

## Why Claims Exist

On platforms like GitHub, repository ownership is managed by the platform itself. If the platform changes, is compromised, or disappears, the ownership record goes with it.

A CodeQuill claim creates a **platform-independent** record of authorship. It does not transfer ownership, restrict access, or assert intellectual property rights. It records a fact: "This workspace authority claims association with this repository."

That fact is recorded on-chain, where it persists regardless of what happens to GitHub, CodeQuill, or any other platform.

## What a Claim Contains

When you claim a repository, the following is recorded on the `CodeQuillRepositoryRegistry` smart contract:

- **Repository ID** -- A `bytes32` identifier derived from the GitHub repository's numeric ID.
- **Context ID** -- The workspace's on-chain identifier.
- **Owner** -- The wallet address claiming the repository.
- **Metadata** -- Additional context (typically the repository name).

Claims are unique: a repository can only be claimed once. If a repository is already claimed, a new claim will fail unless the existing owner transfers it.

## Claiming a Repository

### Prerequisites

Before claiming, you must:

1. Have the repository synced in your workspace (via the GitHub App).
2. Have a connected wallet with an active delegation.
3. Be a member of the workspace on-chain.

### Using the CLI

Navigate to your git repository and run:

```bash
codequill claim
```

The CLI auto-detects the repository from your git remote. It will show a confirmation prompt:

```
Claiming repository: my-org/my-project
Workspace: my-org
Chain: Ethereum

Proceed? (y/N) y

Transaction submitted: 0xabc123...
Waiting for confirmation...
Claimed successfully.
```

### CLI Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--no-confirm` | boolean | false | Skip the confirmation prompt |
| `--confirmations <n>` | integer | 1 | Wait for N on-chain confirmations |
| `--timeout <ms>` | integer | 300000 | Timeout for confirmation (milliseconds) |
| `--no-wait` | boolean | false | Submit transaction and return immediately |
| `--json` | boolean | false | Output result in JSON format |

### Example: CI-Friendly Claim

```bash
codequill claim --no-confirm --json
```

## Claim Transfers

Repository claims can be transferred to a different owner or workspace. The current owner initiates the transfer, specifying the new owner address and (optionally) a new workspace context. The new owner must be a member of the target workspace.

## What a Claim Does Not Do

A claim is evidence, not enforcement:

- It does **not** prevent others from forking or modifying the repository.
- It does **not** assert copyright or intellectual property.
- It does **not** require GitHub to acknowledge or enforce the claim.
- It does **not** guarantee the claimer actually authored the code.

A claim says: "This wallet, associated with this workspace, has established an on-chain record of association with this repository." What weight that record carries depends on the context in which it is evaluated.
