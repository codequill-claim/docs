---
title: Utility Commands
description: "CLI utility commands: wait for on-chain transaction confirmations and explore the philosophy behind CodeQuill with the why command."
order: 4
---

# Utility Commands

These commands provide supporting functionality: waiting for on-chain transactions and accessing explanatory content about how CodeQuill works.

---

## codequill wait

Wait for a blockchain transaction to reach a specified number of confirmations. This is useful when you have submitted a transaction with `--no-wait` and want to separately track its confirmation, or when you need to gate a subsequent step on transaction finality.

### Syntax

```bash
codequill wait <tx_hash> [options]
```

### Arguments

| Argument | Required | Description |
|---|---|---|
| `tx_hash` | yes | The transaction hash to wait for (e.g. `0xabc123...`) |

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--confirmations <n>` | integer | `1` | Number of on-chain confirmations to wait for before returning |
| `--timeout <ms>` | integer | `300000` | Maximum time in milliseconds to wait before timing out |

### Example

```bash
codequill wait 0xabc123def456789...
```

```
Waiting for transaction 0xabc123def456789...
  Confirmations: 0/1

  Confirmations: 1/1
Transaction confirmed.
```

#### Wait for multiple confirmations with a custom timeout

```bash
codequill wait 0xabc123def456789... --confirmations 5 --timeout 600000
```

### Notes

- The default poll interval is **3000ms** (3 seconds). The minimum poll interval is **500ms**. The poll interval is not user-configurable.
- If the transaction is not confirmed within the `--timeout` period, the command exits with a non-zero exit code.
- This command is particularly useful in CI/CD pipelines where you submit a transaction with `--no-wait` in one step and confirm it in a later step:

  ```bash
  # Step 1: Submit without waiting
  TX_HASH=$(codequill publish --no-confirm --no-wait --json | jq -r '.txHash')

  # Step 2: Wait for confirmation separately
  codequill wait "$TX_HASH" --confirmations 3
  ```

- The command queries the blockchain directly to check confirmation count. It does not depend on the CodeQuill backend.

---

## codequill why

Explain why CodeQuill works the way it does. This command prints explanatory content about core concepts and design decisions directly in your terminal. It is intended for onboarding, learning, and quick reference.

### Syntax

```bash
codequill why [topic] [options]
```

### Arguments

| Argument | Required | Description |
|---|---|---|
| `topic` | no | A specific topic to explain. Must be one of: `claim`, `snapshot`, `publish`, `prove`, `attest`, `backup`. If omitted, prints the full overview covering all topics. |

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `--short` | boolean | `false` | Print a brief, condensed explanation instead of the full text |
| `--ci` | boolean | `false` | Output in a CI-friendly format (no colors, no interactive formatting) |

### Example

```bash
codequill why
```

```
CodeQuill produces verifiable evidence records for source code.

A claim establishes who speaks for a repository...
A snapshot captures the exact state of source at a point in time...
Publishing anchors that snapshot on-chain...
...
```

#### Explain a specific topic

```bash
codequill why snapshot
```

```
A snapshot is a deterministic cryptographic fingerprint of your repository's
tracked files at a specific commit.

CodeQuill reads every tracked file, hashes each one with a per-snapshot salt,
and builds a Merkle tree. The root of that tree is the snapshot's identity.
No source code leaves your machine during this process...
```

#### Short explanation for quick reference

```bash
codequill why attest --short
```

```
An attestation links a build artifact to a release, creating a verifiable
chain from source to binary.
```

#### CI-friendly output

```bash
codequill why snapshot --ci
```

### Notes

- If no topic is provided, the full overview is printed, covering all core concepts in sequence.
- The available topics correspond to the major operations in the CodeQuill workflow: `claim`, `snapshot`, `publish`, `prove`, `attest`, and `backup`.
- If both `--short` and `--ci` are set, `--short` takes precedence for content formatting. The `--ci` flag primarily affects output styling (disabling colors and interactive formatting), while `--short` affects content length.
- This command runs entirely offline. It does not contact any server.
