---
title: Encryption Model
order: 2
---

# Encryption Model

CodeQuill uses a zero-custody encryption design to protect preserved source code. The system is structured so that only the workspace authority can decrypt preserved archives. CodeQuill never possesses the decryption key, and no recovery mechanism exists.

## Purpose

Preservations store encrypted copies of full source archives on decentralized storage (IPFS via Lighthouse). The encryption model ensures that these archives are opaque to everyone except the workspace authority -- including CodeQuill itself.

This matters because preservations are stored on public infrastructure. Without encryption, anyone with the IPFS CID could retrieve and read the source code. The encryption model transforms a public storage layer into a private vault, with access controlled entirely by the workspace's passkey.

## Key Derivation: WebAuthn PRF

The encryption key hierarchy begins with the user's passkey. CodeQuill uses the **WebAuthn PRF (Pseudo-Random Function) extension** to derive a stable secret from the passkey credential.

The PRF extension allows a relying party to request a deterministic, credential-bound secret during a WebAuthn assertion. This secret is:

- **Deterministic** -- The same passkey, same credential, and same PRF input always produce the same output.
- **Credential-bound** -- Different passkeys produce different outputs. The secret cannot be derived without the specific credential.
- **Hardware-protected** -- On devices with platform authenticators (Touch ID, Windows Hello, hardware security keys), the PRF computation occurs within the authenticator. The underlying key material is never exposed to software.

This PRF output serves as the master secret from which all workspace encryption keys are derived.

## Key Hierarchy

The full key derivation chain is:

```
Passkey (WebAuthn credential)
  │
  ▼
PRF Extension (deterministic output)
  │
  ▼
Master Secret (stable, credential-bound)
  │
  ▼
X25519 Key Pair
  ├── Public Key  → stored on CodeQuill server
  └── Private Key → never leaves the device
```

1. **Passkey PRF → Master secret.** The user's passkey is used with the PRF extension to derive a stable secret. This secret is the same every time the user authenticates with the same credential.

2. **Master secret → X25519 key pair.** The master secret is used to deterministically generate an X25519 key pair. X25519 is an elliptic-curve Diffie-Hellman function on Curve25519, used here for public-key encryption.

3. **Public key storage.** The public key is sent to the CodeQuill server and associated with the workspace. It is used by the CLI during preservation to encrypt the data encryption key.

4. **Private key isolation.** The private key is never transmitted, stored remotely, or persisted on disk. It exists only in memory during decryption operations, derived on-demand from the passkey.

## Preservation Encryption

When a preservation is created, the CLI uses the `codequill-envelope:v1` encryption scheme:

### Encryption Process

1. **Archive creation.** The CLI creates a deterministic archive of the repository at the snapshot's commit using `git archive` (tar + gzip).

2. **Plaintext hashing.** A SHA-256 hash of the plaintext archive is computed. This hash is anchored on-chain and allows future verification that a decrypted archive matches the original.

3. **DEK generation.** A random Data Encryption Key (32 bytes) and Initialization Vector (12 bytes) are generated using a cryptographically secure random number generator.

4. **Archive encryption.** The archive is encrypted with **AES-256-GCM** using the DEK and IV. AES-256-GCM provides authenticated encryption -- it ensures both confidentiality and integrity. Any tampering with the ciphertext is detected during decryption.

5. **DEK wrapping.** The DEK is wrapped using an **X25519 sealed box** (`crypto_box_seal` from libsodium). A sealed box encrypts a message to a recipient's public key such that only the holder of the corresponding private key can decrypt it. The sender cannot decrypt it after the fact, and the sealed box does not authenticate the sender.

6. **Upload.** The encrypted payload (ciphertext, wrapped DEK, IV, and metadata) is uploaded to IPFS via Lighthouse. The resulting CID is anchored on-chain alongside the plaintext hash.

### Decryption Process

1. **Passkey authentication.** The user authenticates with their passkey, triggering the PRF extension to derive the master secret.

2. **Key derivation.** The X25519 private key is derived from the master secret.

3. **DEK unwrapping.** The wrapped DEK is decrypted using the X25519 private key (`crypto_box_seal_open`).

4. **Archive decryption.** The archive is decrypted using AES-256-GCM with the unwrapped DEK and IV.

5. **Integrity verification.** The SHA-256 hash of the decrypted archive is compared against the on-chain plaintext hash. If they match, the archive is authentic and untampered.

## Zero-Custody Properties

The encryption model is designed to ensure that CodeQuill operates with zero custody over preserved source code:

**CodeQuill never possesses the decryption key.** The private key is derived from the passkey and exists only in the user's browser during decryption. It is never transmitted to CodeQuill servers.

**No admin backdoor.** There is no master key, escrow mechanism, or administrative override. CodeQuill engineers cannot decrypt a preservation under any circumstances.

**No recovery mechanism.** If the passkey is lost, destroyed, or becomes inaccessible, the preservation cannot be decrypted. This is a deliberate design decision: recovery mechanisms require key escrow, which contradicts zero-custody. Users should maintain passkey backups through their authenticator platform (iCloud Keychain, Google Password Manager, hardware key backup).

**Server compromise does not expose data.** Even if an attacker gains full access to CodeQuill servers, they obtain only public keys and encrypted payloads. Without the passkey, the private key cannot be derived and the DEK cannot be unwrapped.

**The server stores only the public key.** The workspace's X25519 public key is the only cryptographic material stored on the server. It enables encryption (anyone can encrypt to the workspace) but not decryption.

## Path Hashing in Snapshots

The encryption key also plays a role in snapshot privacy. File paths in snapshot manifests are hashed with a salt derived from the workspace's master secret:

1. The master secret (from passkey PRF) is used to derive a path salt.
2. Each file path is hashed as `keccak256(salt || path)` before inclusion in the Merkle tree.
3. The manifest contains only these salted path hashes, not plaintext paths.

This ensures that snapshot manifests -- which are stored on IPFS and publicly retrievable -- do not reveal the directory structure of the repository. Verifying that a specific file was included in a snapshot requires knowledge of the salt, which in turn requires workspace authority. See [Proofs](/docs/concepts/proofs) for how selective disclosure works.

## Cryptographic Primitives Summary

| Component | Primitive | Purpose |
|---|---|---|
| Key derivation | WebAuthn PRF extension | Derive stable secret from passkey |
| Key pair | X25519 (Curve25519) | Asymmetric encryption of DEK |
| DEK wrapping | `crypto_box_seal` (libsodium) | Sealed box encryption to public key |
| Archive encryption | AES-256-GCM | Authenticated symmetric encryption |
| Plaintext integrity | SHA-256 | Verify decrypted archive matches original |
| Path hashing | keccak256 with salt | Privacy-preserving file path hashing |
| Merkle tree | keccak256 | Snapshot integrity and proof-of-inclusion |
