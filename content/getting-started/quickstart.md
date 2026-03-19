---
title: Quickstart
description: "Go from zero to a published, on-chain source snapshot in minutes. Sign in, install the CLI, claim a repository, and publish."
order: 2
---

# Quickstart

This guide walks you through the complete flow: from signing in to publishing your first verifiable source snapshot. By the end, you will have a claimed repository with an on-chain evidence record.

## Prerequisites

Before you begin, you will need:

- A **GitHub account** with at least one repository
- **Node.js 18+** installed locally
- A **wallet** (e.g. MetaMask) for on-chain authority
- A **passkey-compatible browser** (Chrome, Safari, Firefox) for encryption setup

## Step 1: Sign In to the Web Application

Visit [app.codequill.xyz](https://app.codequill.xyz) and sign in with your GitHub account. This creates your workspace -- the organizational unit that ties together your repositories, wallets, and collaborators.

After signing in, you will be guided through a setup flow:

1. **Connect a wallet** -- Your wallet becomes the on-chain authority for your workspace. CodeQuill uses Sign-In with Ethereum (SIWE) to verify wallet ownership, then registers your authority on-chain.
2. **Set up encryption** -- Register a passkey (hardware key, biometric, or platform authenticator) that will be used to derive encryption keys for preservations and proofs.
3. **Install the GitHub App** -- This syncs your repositories into CodeQuill so you can claim and snapshot them.

[Screenshot: Workspace setup flow showing wallet connection and GitHub App installation]

## Step 2: Install the CLI

Install the CodeQuill CLI globally:

```bash
npm install -g codequill
```

Verify the installation:

```bash
codequill --version
```

## Step 3: Authenticate the CLI

From your terminal, run:

```bash
codequill login
```

This initiates a device-code authentication flow:

1. The CLI displays a URL and an **approval phrase**.
2. Open the URL in your browser (you must be signed in to the web application).
3. Verify the approval phrase matches what the CLI shows.
4. Approve the login request.

The CLI stores authentication tokens locally at `~/.config/codequill/config.json`. Tokens refresh automatically.

## Step 4: Claim Your Repository

Navigate to your git repository and claim it:

```bash
cd my-project
codequill claim
```

Claiming records an on-chain evidence statement that your workspace authority is associated with this repository. It does not transfer ownership or restrict access -- it establishes a verifiable record of authorship.

The CLI will display a confirmation prompt showing the repository name and your workspace. Approve it, and the claim transaction is submitted to the blockchain.

```
Claiming repository: my-org/my-project
Workspace: my-org
Chain: Ethereum

Proceed? (y/N) y

Transaction submitted: 0xabc123...
Waiting for confirmation...
Claimed successfully.
```

## Step 5: Create a Snapshot

A snapshot produces a deterministic cryptographic fingerprint of your repository's source state at the current commit:

```bash
codequill snapshot
```

This runs entirely locally. It reads every tracked file, hashes them individually, and builds a Merkle tree. The resulting manifest is written to `.codequill/snapshots/`.

No source code is uploaded. The snapshot captures structure and content hashes -- not the content itself.

## Step 6: Publish the Snapshot

Publishing anchors your snapshot as a durable, timestamped record:

```bash
codequill publish
```

This uploads the snapshot manifest to decentralized storage (IPFS via Lighthouse) and records the Merkle root on-chain. The result is an immutable evidence record that your source code existed in this exact state at this moment.

```
Publishing snapshot for commit a1b2c3d...
Uploading manifest to IPFS...
Anchoring on-chain...

Transaction submitted: 0xdef456...
Waiting for confirmation...

Published successfully.
  Snapshot ID: snap_abc123
  Merkle Root: 0x789...
  Manifest CID: bafybeig...
  Explorer: https://sepolia.etherscan.io/tx/0xdef456...
```

## What You Have Now

After completing these steps, you have:

- A **claimed repository** with an on-chain authorship record
- A **published snapshot** with a verifiable Merkle root anchored on Ethereum
- A **manifest** stored on decentralized storage (IPFS) that anyone can retrieve and verify

This is the foundation. From here, you can:

- **Create releases** -- Bind a snapshot to a named version with governance workflows. See [Releases](/concepts/releases).
- **Attest artifacts** -- Link build artifacts to releases, creating a verifiable lineage chain. See [Attestations](/concepts/attestations).
- **Preserve source** -- Create encrypted archives tied to snapshots for long-term survival. See [Preservations](/concepts/preservations).
- **Generate proofs** -- Prove that a specific file was included in a snapshot without revealing the full tree. See [Proofs](/concepts/proofs).
- **Automate in CI** -- Set up GitHub Actions to snapshot and attest automatically. See [CI/CD Integration](/ci-cd/overview).

## Mental Model

Think of CodeQuill as producing **software receipts**. Like a receipt from a transaction, a CodeQuill snapshot is a verifiable record of what existed and when. It does not control what happens next -- it preserves what already happened.

CodeQuill is infrastructure. It should feel boring, restrained, and precise. The value is not in the tooling itself, but in the evidence it preserves.
