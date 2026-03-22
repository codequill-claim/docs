---
title: Trust Index
description: "The Trust Index is an evidence-based reputation signal computed from verifiable source activity. Learn how it works, what it measures, and how to improve your score."
order: 8
---

# Trust Index

The CodeQuill Trust Index is a non-transferable, evidence-based reputation signal computed from verifiable, on-chain source activity. It produces a score from 0 to 100 that summarizes the breadth, depth, and consistency of a repository's evidence footprint.

The Trust Index is computed at two levels:

- **Per-repository** -- the primary unit of computation, derived from a repository's snapshots, releases, attestations, preservations, and dependency graph.
- **Per-workspace** -- a weighted aggregate of all repository scores within a workspace.

Scores are recomputed daily and displayed across the CodeQuill platform: on repository pages, the dashboard, public workspace profiles, and certificate pages.

---

## Why the Trust Index Exists

Software trust today relies on indirect, social signals: GitHub stars, download counts, brand recognition, team reputation. These signals are valuable but fundamentally **disconnected from verifiable evidence**. A popular project may have no on-chain evidence of its source history. A lesser-known project may have 18 months of continuous, verified snapshots with external attestors.

The Trust Index is designed to complement -- not replace -- social signals by asking a more specific question:

> *"How much verifiable, long-lived, independently referenced source evidence exists for this repository?"*

This question can be answered deterministically from on-chain records. No subjective inputs. No manual overrides. Every point in the score maps back to a specific, verifiable action.

### The Gap Between Trust and Verification

When a developer evaluates a dependency, they typically check:

1. Is it actively maintained? *(GitHub commit activity)*
2. Is it widely used? *(Download counts, stars)*
3. Does it have a credible team? *(Social signals, brand)*

None of these answer: "Can I verify what source code was used to produce the build artifact I'm about to install?" or "Has this project consistently published cryptographic evidence of its source state?"

The Trust Index fills this gap. It measures the **observable evidence** of a project's commitment to verifiable software practices -- and it does so using records that are immutable, timestamped, and independently verifiable.

---

## The Six Factors

The Trust Index is computed as a weighted sum of six independent subscores. Each factor captures a different dimension of verifiable software evidence.

### Snapshot Activity (25%)

Snapshots are the foundational unit of evidence in CodeQuill. This factor measures the volume of published, on-chain snapshots for a repository.

- More snapshots indicate a higher commitment to producing verifiable evidence.
- The score uses **logarithmic scaling** to prevent gaming through volume. Publishing 1,000 snapshots scores only marginally more than publishing 100.
- The score saturates at approximately 100 published snapshots.

A repository with a single snapshot will receive a small but non-zero score. The marginal value of each additional snapshot decreases, rewarding consistent publishing behavior over burst activity.

### Continuity (20%)

Evidence produced once is valuable. Evidence produced consistently over months and years is substantially more valuable. The Continuity factor measures the time span and regularity of snapshot activity.

The key inputs are:

- **Active months**: The number of distinct calendar months in which at least one snapshot was published.
- **Maximum gap**: The longest period of inactivity between consecutive snapshots.

The score increases with more active months (up to 18 months of active evidence for a full score) but is reduced by a **gap penalty**:

| Maximum gap | Penalty |
|-------------|---------|
| 90 days or less | None |
| 91 -- 180 days | 20% reduction |
| 181 -- 365 days | 40% reduction |
| Over 365 days | 60% reduction |

This means a repository that published snapshots for 12 months but then went silent for a year will score lower than one with 12 months of continuous activity. The Continuity factor rewards sustained commitment to evidence production.

### Release Governance (20%)

Snapshots capture raw evidence. Releases represent **intent** -- a deliberate decision that a particular source state should be treated as a versioned unit. The Release Governance factor measures release maturity, cadence, and governance outcomes.

Three components contribute to the score:

1. **Release cadence** (50% of this factor): The number of distinct months with at least one published release, up to 12 months for a full subscore.
2. **Volume** (30%): The total number of published releases, log-scaled with diminishing returns.
3. **Governance ratio** (20%): The proportion of published releases that have been explicitly **accepted** by a governance authority. A repository where most releases are accepted scores higher than one where releases remain in a pending state.

Revoked releases are excluded from all counts. This factor rewards projects that publish releases regularly, with proper governance decisions, over sustained periods.

### Attestations (15%)

Attestations bind build artifacts to source releases. This factor measures both the volume of attestation activity and, critically, whether **external workspaces** have independently attested artifacts for this repository's releases.

Three components contribute:

1. **Self-attestation volume** (30%): Total attestations, log-scaled. This captures the project's own attestation discipline.
2. **External attestors** (40%): The number of unique external workspaces that have attested artifacts. This is the most valuable signal -- it represents independent verification by third parties.
3. **Attestation cadence** (30%): Distinct months with at least one attestation, up to 12 months.

The external attestor component is deliberately weighted highest because it represents something the repository owner cannot fabricate: independent, third-party verification. Without any external attestors, the maximum Attestation subscore is capped at approximately 60% of its potential.

Multiple attestations from the same external workspace count only once (uniqueness enforcement).

### Preservation Coverage (10%)

Preservations are encrypted, zero-custody archives of source code tied to published snapshots. The Preservation factor measures what percentage of a repository's snapshots have been preserved.

The formula is straightforward: if a repository has 10 published snapshots and 8 of them have at least one confirmed preservation, the score is 80%.

This factor rewards operational maturity. Creating a snapshot is the minimum act of evidence production. Preserving it demonstrates a deeper commitment to long-term recoverability and custody of source records.

### Dependency Graph (10%)

Software does not exist in isolation. When other projects declare your repository as an upstream dependency (via attestation upstream declarations), it signals real-world reliance on your code. The Dependency Graph factor measures participation in this verifiable dependency network.

The key inputs are:

- **Valid downstreams**: Unique repositories or workspaces that reference this repository as an upstream dependency.
- **Valid upstreams**: Upstream dependencies this repository itself declares.

Both are counted only when backed by anchored evidence (confirmed attestations or snapshots). The score uses logarithmic scaling with a cap at 15 total connections.

Downstream dependents are more valuable than self-declared upstreams because they represent independent reliance rather than self-assertion. Future versions of the Trust Index may weight downstreams more heavily.

---

## Trust Tiers

Scores are mapped to human-readable tiers that provide an at-a-glance reputation signal:

| Score | Tier | Meaning |
|-------|------|---------|
| 0 -- 20 | **New** | Recently started publishing evidence. Limited history. |
| 21 -- 40 | **Emerging** | Building a track record. Some verifiable activity over time. |
| 41 -- 60 | **Established** | Consistent evidence production with governance and preservation. |
| 61 -- 80 | **Trusted** | Strong, long-lived evidence footprint with external verification. |
| 81 -- 100 | **Proven** | Exceptional evidence record with sustained external reliance. |

These labels are displayed on repository pages, certificates, public profiles, and badges.

---

## Workspace Trust Index

The workspace Trust Index is a weighted aggregate of all repository scores within the workspace. Repositories with zero snapshots contribute nothing and do not dilute the score.

The weighting formula uses the logarithm of each repository's snapshot count:

```
workspace_weight(repo) = log(1 + repo.snapshot_count)
```

This ensures that repositories with more evidence contribute proportionally more to the workspace score, while preventing a single highly-active repository from completely dominating.

A workspace with five repositories where two are actively maintained (high scores) and three are empty (zero scores) will have a workspace Trust Index reflecting only the two active repositories.

---

## Anti-Gaming Design

The Trust Index is designed to be resistant to manipulation. Several mechanisms work together to ensure that scores reflect genuine evidence rather than synthetic activity.

### Logarithmic Scaling

All volume-based metrics (snapshot count, release count, attestation count) use logarithmic scaling. This means:

- Publishing 10 snapshots scores significantly more than 1.
- Publishing 100 scores moderately more than 10.
- Publishing 1,000 scores only slightly more than 100.

Volume alone cannot push a score to the top. Sustained activity over time, governance decisions, and external verification are required.

### Self-Only Ceiling

A repository that only produces evidence about itself -- snapshots, releases, self-attestations, preservations -- without any external attestors or downstream dependents can achieve a maximum Trust Index of approximately **70 out of 100**.

Reaching the highest tiers (Trusted and Proven) requires independent external signals: other workspaces attesting your artifacts or declaring your repository as an upstream dependency. This ceiling ensures that the Trust Index reflects real-world reliance, not just self-assertion.

### Uniqueness Enforcement

External signals are counted once per workspace. If the same external workspace attests 100 artifacts from your repository, it counts as one external attestor. This prevents collusion between two parties from inflating scores.

### Inactivity Decay

The Trust Index accounts for projects that were once active but have become dormant. If no new snapshots, releases, or attestations are published for 6 months:

- The score begins decaying at 5% per quarter (every 3 months).
- The minimum decay floor is 50% of the computed score -- historical evidence retains baseline value even if the project becomes inactive.
- Any new evidence activity (a single snapshot, release, or attestation) immediately resets the decay clock.

This ensures that the Trust Index reflects **current** evidence production, not just historical activity. A project that was highly active two years ago but has published nothing since will see its score gradually decrease, while still retaining credit for its historical evidence.

### Deterministic Computation

Scores are computed entirely from on-chain evidence records. There are no manual overrides, no appeals process, and no subjective inputs. The same on-chain data will always produce the same score.

---

## What the Trust Index is NOT

The Trust Index is an informational signal. It is important to understand its boundaries:

- **It is not a security guarantee.** A high Trust Index does not mean the code is free of vulnerabilities or that build artifacts are safe to use.
- **It is not proof of build correctness.** The Trust Index measures evidence production, not the relationship between source code and compiled artifacts.
- **It is not a popularity metric.** Download counts, GitHub stars, and social following have no effect on the score.
- **It is not a competitive ranking.** There are no leaderboards or comparisons between workspaces. Each score is independent.
- **It is not a substitute for code review.** Always review dependencies regardless of their Trust Index score.

The Trust Index answers one question: "How much verifiable, long-lived, independently referenced source evidence exists?" It is one signal among many that informed developers and auditors should consider.

---

## Embedding Trust Index Badges

You can embed a Trust Index badge in your repository README to display the current score:

### Markdown

```markdown
[![CodeQuill Trust Index](https://app.codequill.xyz/badges/trust/YOUR_REPO_ID)](https://app.codequill.xyz/repositories/YOUR_REPO_ID)
```

The badge updates automatically as scores are recomputed. The color matches the current tier:

- Grey for New
- Amber for Emerging
- Blue for Established
- Green for Trusted
- Purple for Proven

You can find your repository's badge URL on the **Badges** tab in the repository settings within CodeQuill.

---

## Versioning

The Trust Index specification is versioned:

- **v1** (deprecated): Workspace-level only, without Release governance or per-repository scoring.
- **v2** (current): Per-repository and workspace aggregate scoring with six factors including Release governance, Attestation breakdown with external attestor detection, Preservation coverage, and inactivity decay.

Future versions may adjust weights (with version pinning), add per-chain scoring, incorporate CI/CD automation signals, or introduce time-weighted external signal decay.
